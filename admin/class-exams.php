<?php
if (!defined('ABSPATH')) {
    exit;
}

class LWA_EXAMS_Exams
{
    private $exams_table;
    private $categories_table;
    private $exam_categories_table;

    public function __construct()
    {

        global $wpdb;
        $this->exams_table = $wpdb->prefix . 'exams';
        $this->categories_table = $wpdb->prefix . 'categories';
        $this->exam_categories_table = $wpdb->prefix . 'exam_categories';

        // Register the form handler
        add_action('admin_post_lwa_exams_save_exam', [$this, 'handle_form_submissions']);
        // Optional: for non-logged-in users
        add_action('admin_post_nopriv_lwa_exams_save_exam', [$this, 'handle_no_privileges']);
        add_action('admin_post_lwa_exams_delete_exam', [$this, 'handle_delete_exam']);
        add_action('admin_post_lwa_exams_bulk_delete_exams', [$this, 'handle_bulk_delete_exams']);
        add_action('admin_post_nopriv_lwa_exams_bulk_delete_exams', [$this, 'handle_no_privileges']);
    }

    public function render_admin_exams_page()
    {
        // Handle messages
        if (isset($_GET['message'])) {
            $message = urldecode($_GET['message']);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        // Handle ERROR messages
        if ($error = get_transient('lwa_exams_exam_error')) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            delete_transient('lwa_exams_exam_error');
        }

        // Get current action
        $action = isset($_GET['action_exams']) ? sanitize_text_field($_GET['action_exams']) : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'edit' && $id) {
            $exam = $this->get_exam($id);
            if (!$exam) {
                wp_die(__('Exam not found', 'lwa-exams'));
            }
            $exam->categories = $this->get_exam_categories($id);
        }

        // Get filter and search parameters
        $filter = (isset($_GET['exam_filter']) && $_GET['exam_filter'] !== '') ? intval($_GET['exam_filter']) : null;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Fetch exams with filter and search applied
        $exams_data = $this->get_exams($filter, $search);
        $exams = $exams_data['items'];
        $total_items = $exams_data['total_items'];
        $per_page = $exams_data['per_page'];
        $all_categories = $this->get_categories();

        include LWA_EXAMS_PATH . 'admin/views/exams.php';
    }

    public function handle_form_submissions()
    {

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        // Verify nonce
        if (!isset($_POST['lwa_exams_exams_nonce']) || !wp_verify_nonce($_POST['lwa_exams_exams_nonce'], 'lwa_exams_exams')) {
            wp_die('Security check failed!');
        }


        // Single call to process_exam_form() which handles its own redirect
        $this->process_exam_form();
    }

    public function handle_no_privileges()
    {
        wp_die('You must be logged in to submit this form.');
    }

    private function process_exam_form()
    {
        global $wpdb;

        // Sanitize input data
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $time_limit = intval($_POST['time_limit']);
        $passing_score = intval($_POST['passing_score']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $categories = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : [];
        $action = sanitize_text_field($_POST['action_exams']);

        if ($action === 'create_exam') {
            $exam_id = $this->create_exam($title, $description, $time_limit, $passing_score, $is_active);
            $message = __('Exam added successfully', 'lwa-exams');
        } elseif ($action === 'update_exam' && isset($_POST['id'])) {
            $exam_id = intval($_POST['id']);
            $this->update_exam($exam_id, $title, $description, $time_limit, $passing_score, $is_active);
            $message = __('Exam updated successfully', 'lwa-exams');
        }

        if (isset($exam_id) && $exam_id) {
            $success = $this->update_exam_categories($exam_id, $categories);
            if (false === $success) {
                return false; // Return false if category update failed
            }

            wp_redirect(admin_url('admin.php?page=lwa-exams-exams&message=' . urlencode($message)));
            exit;
        }

        if (!isset($exam_id)) {
            set_transient('lwa_exams_category_error', 'Invalid form action', 45);
        }

        return false; // If we got here, no exam_id was set
    }

    private function create_exam($title, $description, $time_limit, $passing_score, $is_active)
    {
        global $wpdb;

        $result = $wpdb->insert(
            $this->exams_table,
            [
                'title' => $title,
                'description' => $description,
                'time_limit_minutes' => $time_limit,
                'passing_score' => $passing_score,
                'is_active' => $is_active

            ],
            ['%s', '%s', '%d', '%d', '%d']
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    private function update_exam($exam_id, $title, $description, $time_limit, $passing_score, $is_active)
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->exams_table,
            [
                'title' => $title,
                'description' => $description,
                'time_limit_minutes' => $time_limit,
                'passing_score' => $passing_score,
                'is_active' => $is_active,
            ],
            ['id' => $exam_id],
            ['%s', '%s', '%d', '%d', '%d'],
            ['%d']
        );
        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }
    }

    private function update_exam_categories($exam_id, $categories)
    {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Delete existing categories
            $deleted = $wpdb->delete(
                $this->exam_categories_table,
                ['exam_id' => $exam_id],
                ['%d']
            );

            if (false === $deleted) {
                throw new Exception($wpdb->last_error);
            }

            // Batch insert new categories
            if (!empty($categories)) {
                $values = [];
                foreach ($categories as $category_id) {
                    $values[] = $wpdb->prepare('(%d,%d)', $exam_id, $category_id);
                }

                $result = $wpdb->query(
                    "INSERT INTO {$this->exam_categories_table} 
                    (exam_id, category_id) VALUES " . implode(',', $values)
                );

                if (false === $result) {
                    throw new Exception($wpdb->last_error);
                }
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            // Store error in transient to display on next page load
            set_transient(
                'lwa_exams_category_error',
                'Failed to update categories: ' . esc_html($e->getMessage()),
                45 // Display for 45 seconds
            );

            return false;
        }
    }

    public function get_exams($filter = null, $search = '')
    {
        global $wpdb;

        // Base query parts
        $query = "SELECT e.*, COUNT(q.id) AS question_count
            FROM {$this->exams_table} e
            LEFT JOIN {$wpdb->prefix}questions q ON e.id = q.exam_id";

        // Count query for pagination
        $count_query = "SELECT COUNT(DISTINCT e.id)
                   FROM {$this->exams_table} e";

        // Initialize conditions and parameters
        $conditions = [];
        $params = [];

        // Handle category filter
        if (!is_null($filter)) {
            $conditions[] = 'e.id = %d';
            $params[] = intval($filter);
        }

        // Handle search query
        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($search)) . '%';
            $conditions[] = '(e.title LIKE %s OR e.description LIKE %s)';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Add WHERE clause if conditions exist
        if (!empty($conditions)) {
            $where_clause = ' WHERE ' . implode(' AND ', $conditions);
            $query .= $where_clause;
            $count_query .= $where_clause;
        }

        // Get pagination parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 10;
        $offset = ($current_page - 1) * $per_page;

        // Add GROUP BY and ORDER BY to main query
        $query .= ' GROUP BY e.id ORDER BY e.created_at DESC LIMIT %d OFFSET %d';

        // Get total count of items (for pagination)
        if (!empty($params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Execute main query
        if (!empty($params)) {
            $params[] = $per_page;
            $params[] = $offset;
            $exams = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            $query = str_replace(
                ['LIMIT %d OFFSET %d'],
                ["LIMIT {$per_page} OFFSET {$offset}"],
                $query
            );
            $exams = $wpdb->get_results($query);
        }

        return [
            'items' => $exams,
            'total_items' => $total_items,
            'per_page' => $per_page,
        ];
    }

    public function get_categories()
    {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$this->categories_table} ORDER BY name"
        );
    }

    public function get_exam_categories($exam_id)
    {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "SELECT category_id FROM {$this->exam_categories_table} WHERE exam_id = %d",
            $exam_id
        ));
    }

    public function get_exam($exam_id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->exams_table} WHERE id = %d",
            $exam_id
        ));
    }



    public function handle_delete_exam()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'lwa_exams_delete_exam')) {
            wp_die('Security check failed');
        }

        $id = intval($_GET['id']);
        $this->delete_exam($id);

        wp_redirect(admin_url('admin.php?page=lwa-exams-exams&message=' . urlencode(__('Exam deleted successfully', 'lwa-exams'))));
        exit;
    }

    public function handle_bulk_delete_exams()
    {
        // Verify permissions
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        // Verify nonce
        check_admin_referer('bulk-exams');

        // Check if bulk action is delete
        if (!isset($_POST['bulk_action']) || $_POST['bulk_action'] !== 'delete') {
            wp_redirect(admin_url('admin.php?page=lwa-exams-exams'));
            exit;
        }

        // Check if categories were selected
        if (empty($_POST['exam_ids'])) {
            wp_redirect(admin_url('admin.php?page=lwa-exams-exams'));
            exit;
        }

        // Process deletions
        $deleted_count = 0;
        foreach ($_POST['exam_ids'] as $exam_id) {
            $result = $this->delete_exam(intval($exam_id));
            if ($result !== false) {
                $deleted_count++;
            }
        }

        // Set success message
        $message = sprintf(
            _n('%d exam deleted successfully', '%d exams deleted successfully', $deleted_count, 'lwa-exams'),
            $deleted_count
        );

        // Redirect with message
        wp_redirect(admin_url('admin.php?page=lwa-exams-exams&message=' . urlencode($message)));
        exit;
    }

    private function delete_exam($exam_id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->exams_table,
            ['id' => $exam_id],
            ['%d']
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $result;
    }
}
