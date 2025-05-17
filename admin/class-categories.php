<?php
if (!defined('ABSPATH')) {
    exit;
}

class LWA_EXAMS_Categories
{
    private $categories_table;

    public function __construct()
    {
        global $wpdb;
        $this->categories_table = $wpdb->prefix . 'categories';

        // Register the form handlers
        add_action('admin_post_lwa_exams_save_categories', [$this, 'handle_form_submissions']);
        add_action('admin_post_nopriv_lwa_exams_save_categories', [$this, 'handle_no_privileges']);
        add_action('admin_post_lwa_exams_delete_category', [$this, 'handle_delete_category']);
        add_action('admin_post_lwa_exams_bulk_delete_categories', [$this, 'handle_bulk_delete_categories']);
        add_action('admin_post_nopriv_lwa_exams_bulk_delete_categories', [$this, 'handle_no_privileges']);
    }



    public function render_admin_categories_page()
    {
        // Handle messages
        if (isset($_GET['message'])) {
            $message = urldecode($_GET['message']);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    
        // Handle ERROR messages
        if ($error = get_transient('lwa_exams_category_error')) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            delete_transient('lwa_exams_category_error');
        }
    
        // Get current action
        $action = isset($_GET['action_categories']) ? sanitize_text_field($_GET['action_categories']) : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
        if ($action === 'edit' && $id) {
            $category = $this->get_category($id);
            if (!$category) {
                wp_die(__('Category not found', 'lwa-exams'));
            }
        }
    
        // Get filter and search parameters
        $filter = isset($_GET['category_filter']) ? intval($_GET['category_filter']) : 0;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
        // Fetch categories with filter and search applied
        $categories = $this->get_categories($filter, $search);
    
        include LWA_EXAMS_PATH . 'admin/views/categories.php';
    }
    















    public function handle_form_submissions()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        if (!isset($_POST['lwa_exams_categories_nonce']) || !wp_verify_nonce($_POST['lwa_exams_categories_nonce'], 'lwa_exams_categories')) {
            wp_die('Security check failed!');
        }

        $this->process_categories_form();
    }

    public function handle_no_privileges()
    {
        wp_die('You must be logged in to submit this form.');
    }

    private function process_categories_form()
    {
        global $wpdb;
    
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $action = sanitize_text_field($_POST['action_categories']);
    
        try {
            // Check for existing category with the same name

            $existing_category = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$this->categories_table} WHERE LOWER(name) = LOWER(%s)",
                $name
            ));
    
            if ($action === 'create_category') {
                // For new categories, check if name exists
                if ($existing_category) {
                    throw new Exception(__('A category with this name already exists.', 'lwa-exams'));
                }
                
                $category_id = $this->create_category($name, $description);
                $message = __('Category added successfully', 'lwa-exams');
            } elseif ($action === 'update_category' && isset($_POST['id'])) {
                $category_id = intval($_POST['id']);
                
                // For updates, only check if name exists for a different category
                if ($existing_category && $existing_category->id != $category_id) {
                    throw new Exception(__('A category with this name already exists.', 'lwa-exams'));
                }
                
                $this->update_category($category_id, $name, $description);
                $message = __('Category updated successfully', 'lwa-exams');
            } else {
                throw new Exception('Invalid form action');
            }
    
            wp_redirect(admin_url('admin.php?page=lwa-exams-categories&message=' . urlencode($message)));
            exit;
        } catch (Exception $e) {
            set_transient('lwa_exams_category_error', $e->getMessage(), 45);
            $redirect_url = $action === 'create_category' 
                ? admin_url('admin.php?page=lwa-exams-categories&action_categories=add')
                : admin_url('admin.php?page=lwa-exams-categories&action_categories=edit&id=' . (isset($_POST['id']) ? intval($_POST['id']) : 0));
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    private function create_category($name, $description)
    {
        global $wpdb;

        $result = $wpdb->insert(
            $this->categories_table,
            [
                'name' => $name,
                'description' => $description
            ]
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    private function update_category($category_id, $name, $description)
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->categories_table,
            [
                'name' => $name,
                'description' => $description
            ],
            ['id' => $category_id],
            ['%s', '%s'],
            ['%d']
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }
    }

    public function get_categories($filter = 0, $search = '')
    {
        global $wpdb;
    
        // Base query
        $query = "SELECT * FROM {$this->categories_table}";
    
        // Initialize conditions and parameters
        $conditions = [];
        $params = [];
    
        // Handle category filter
        if ($filter > 0) {
            $conditions[] = 'id = %d';
            $params[] = intval($filter);
        }
    
        // Handle search query
        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($search)) . '%';
            $conditions[] = '(name LIKE %s OR description LIKE %s)';
            $params[] = $search_term;
            $params[] = $search_term;
        }
    
        // Add conditions to query if any exist
        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }
    
        // Add ordering
        $query .= ' ORDER BY name';
    
        // Prepare and execute the query
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            return $wpdb->get_results($query);
        }
    }




    public function get_category($category_id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->categories_table} WHERE id = %d",
            $category_id
        ));
    }

    public function handle_delete_category()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'lwa_exams_delete_category')) {
            wp_die('Security check failed');
        }

        $id = intval($_GET['id']);
        $this->delete_category($id);

        wp_redirect(admin_url('admin.php?page=lwa-exams-categories&message=' . urlencode(__('Category deleted successfully', 'lwa-exams'))));
        exit;
    }

    public function handle_bulk_delete_categories()
    {
        // Verify permissions
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        // Verify nonce
        check_admin_referer('bulk-categories');

        // Check if bulk action is delete
        if (!isset($_POST['bulk_action']) || $_POST['bulk_action'] !== 'delete') {
            wp_redirect(admin_url('admin.php?page=lwa-exams-categories'));
            exit;
        }

        // Check if categories were selected
        if (empty($_POST['category_ids'])) {
            wp_redirect(admin_url('admin.php?page=lwa-exams-categories'));
            exit;
        }

        // Process deletions
        $deleted_count = 0;
        foreach ($_POST['category_ids'] as $category_id) {
            $result = $this->delete_category(intval($category_id));
            if ($result !== false) {
                $deleted_count++;
            }
        }

        // Set success message
        $message = sprintf(
            _n('%d category deleted successfully', '%d categories deleted successfully', $deleted_count, 'lwa-exams'),
            $deleted_count
        );

        // Redirect with message
        wp_redirect(admin_url('admin.php?page=lwa-exams-categories&message=' . urlencode($message)));
        exit;
    }

    private function delete_category($category_id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->categories_table,
            ['id' => $category_id],
            ['%d']
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $result;
    }
}
