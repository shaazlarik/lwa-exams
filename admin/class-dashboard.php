<?php

if (! defined('ABSPATH')) {
    exit;
}

class LWA_EXAMS_Dashboard
{

    private $attempts_table;
    private $users_table;
    private $exams_table;

    public function __construct()
    {

        global $wpdb;
        $this->attempts_table = $wpdb->prefix . 'attempts';
        $this->users_table = $wpdb->prefix . 'users';
        $this->exams_table = $wpdb->prefix . 'exams';

        add_action('admin_menu', [$this, 'lwa_exams_register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'lwa_exams_enqueue_assets']);

        // Register the form handlers
        add_action('admin_post_lwa_exams_delete_attempt', [$this, 'handle_delete_attempt']);
        add_action('admin_post_lwa_exams_bulk_delete_attempts', [$this, 'handle_bulk_delete_attempts']);
        add_action('admin_post_nopriv_lwa_exams_bulk_delete_attempts', [$this, 'handle_no_privileges']);
    }

    public static function activate()
    {
        self::create_tables();
    }
    public static function deactivate()
    {
        // clearance task
    }

    public static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create attempts table
        $sql = "CREATE TABLE {$wpdb->prefix}attempts (
            id int NOT NULL AUTO_INCREMENT,
            user_id int NOT NULL,
            exam_id int NOT NULL,
            score int DEFAULT NULL,
            total_questions int DEFAULT NULL,
            percentage decimal(5,2) DEFAULT NULL,
            passed tinyint(1) DEFAULT NULL,
            start_time timestamp NULL DEFAULT NULL,
            end_time timestamp NULL DEFAULT NULL,
            time_taken_seconds int DEFAULT NULL,
            status varchar(20) DEFAULT 'completed',
            question_order TEXT DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY exam_id (exam_id),
            KEY idx_user_endtime (user_id, end_time)
        ) $charset_collate;";
        dbDelta($sql);

        // Create attempt_answers table
        $sql = "CREATE TABLE {$wpdb->prefix}attempt_answers (
            id int NOT NULL AUTO_INCREMENT,
            attempt_id int NOT NULL,
            question_id int NOT NULL,
            selected_answer varchar(20) DEFAULT NULL,
            is_correct tinyint(1) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY attempt_id (attempt_id),
            KEY question_id (question_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Create categories table
        $sql = "CREATE TABLE {$wpdb->prefix}categories (
            id int NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_name (name)
        ) $charset_collate;";
        dbDelta($sql);

        // Create exams table
        $sql = "CREATE TABLE {$wpdb->prefix}exams (
            id int NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            time_limit_minutes int DEFAULT '30',
            passing_score int DEFAULT '70',
            is_active tinyint(1) DEFAULT '1',
            created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_created (created_at)
        ) $charset_collate;";
        dbDelta($sql);

        // Create exam_categories table
        $sql = "CREATE TABLE {$wpdb->prefix}exam_categories (
            exam_id int NOT NULL,
            category_id int NOT NULL,
            PRIMARY KEY (exam_id, category_id),
            KEY category_id (category_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Create questions table
        $sql = "CREATE TABLE {$wpdb->prefix}questions (
            id int NOT NULL AUTO_INCREMENT,
            exam_id int NOT NULL,
            type varchar(50) DEFAULT 'Multiple Choice',
            question_number int NOT NULL,
            question_text text NOT NULL,
            question_image varchar(255) DEFAULT NULL,
            has_image tinyint(1) DEFAULT 0,
            option_a varchar(255) DEFAULT NULL,
            option_b varchar(255) DEFAULT NULL,
            option_c varchar(255) DEFAULT NULL,
            option_d varchar(255) DEFAULT NULL,
            correct_answer varchar(20) DEFAULT NULL,
            explanation text,
            option_a_image varchar(255) DEFAULT NULL,
            option_b_image varchar(255) DEFAULT NULL,
            option_c_image varchar(255) DEFAULT NULL,
            option_d_image varchar(255) DEFAULT NULL,
            created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exam_id (exam_id),
            KEY idx_exam (exam_id)
        ) $charset_collate;";
        dbDelta($sql);
    }


    public static function uninstall()
    {
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            exit();
        }

        global $wpdb;

        // 1. Drop all plugin tables
        $tables = [
            'attempts',
            'attempt_answers',
            'categories',
            'exams',
            'exam_categories',
            'questions'
        ];

        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");

            if ($wpdb->last_error) {
                error_log("[LWA Exams] Failed to drop table {$table_name}: " . $wpdb->last_error);
            }
        }

        // 2. Clean up options
        $options = ['lwa_exams_version'];
        foreach ($options as $option) {
            if (get_option($option) !== false) {
                delete_option($option);
            }
        }

        // 3. Clean up transients
        $transients = [
            'lwa_exams_category_error',
            'lwa_exams_exam_error',
            'lwa_exams_question_error'
        ];

        foreach ($transients as $transient) {
            delete_transient($transient);
        }

        // 4. Clear scheduled events
        wp_clear_scheduled_hook('lwa_exams_daily_maintenance');
    }




    // Add admin menu
    function lwa_exams_register_menu()
    {
        $lwa_menu_hook = add_menu_page(
            'LWA Dashboard',
            'LWA Exams',
            'manage_options',
            'lwa-exams',
            [$this, 'render_dashboard'],
            'dashicons-welcome-learn-more',
            6
        );

        // Replace the default first submenu with a custom label like 'Dashboard'
        add_submenu_page(
            'lwa-exams',               // Parent slug
            'LWA Exams',               // Page title
            'Dashboard',               // Submenu title
            'manage_options',
            'lwa-exams',               // Same slug as main menu
            [$this, 'render_dashboard']
        );

        add_submenu_page(
            'lwa-exams',
            'Manage Categories',
            'Categories',
            'manage_options',
            'lwa-exams-categories',
            [$this, 'load_lwa_categories_page']
        );

        add_submenu_page(
            'lwa-exams',
            'Manage Exams',
            'Exams',
            'manage_options',
            'lwa-exams-exams',
            [$this, 'load_lwa_exams_page']
        );

        add_submenu_page(
            'lwa-exams',
            'Manage Questions',
            'Questions',
            'manage_options',
            'lwa-exams-questions',
            [$this, 'load_lwa_questions_page']
        );
    }

    public function load_lwa_exams_page()
    {
        require_once LWA_EXAMS_PATH . 'admin/class-exams.php';
        $exams = new LWA_EXAMS_Exams();
        $exams->render_admin_exams_page();
    }

    public function load_lwa_categories_page()
    {
        require_once LWA_EXAMS_PATH . 'admin/class-categories.php';
        $categories = new LWA_EXAMS_Categories();
        $categories->render_admin_categories_page();
    }

    public function load_lwa_questions_page()
    {
        require_once LWA_EXAMS_PATH . 'admin/class-questions.php';
        $questions = new LWA_EXAMS_Questions();
        $questions->render_admin_questions_page();
    }

    public function render_dashboard()
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

        // Check if we're viewing results
        if (isset($_GET['action']) && $_GET['action'] === 'results' && isset($_GET['attempt_id'])) {
            echo $this->render_attempt_results(intval($_GET['attempt_id']));
            return;
        }

        // Get current action
        $action = isset($_GET['action_attempts']) ? sanitize_text_field($_GET['action_attempts']) : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Get filter and search parameters
        $filter = isset($_GET['attempt_filter']) ? intval($_GET['attempt_filter']) : 0;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Fetch attempts with filter and search applied
        $attempts_data = $this->get_attempts($filter, $search);

        // Pass data to view
        $attempts = $attempts_data['items'];
        $total_items = $attempts_data['total_items'];
        $per_page = $attempts_data['per_page'];

        include LWA_EXAMS_PATH . 'admin/views/dashboard.php';
    }

    protected function render_attempt_results($attempt_id)
    {
        global $wpdb;

        // Verify attempt exists
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, e.title, e.passing_score, u.display_name as user_name 
        FROM {$this->attempts_table} a
        JOIN {$this->exams_table} e ON a.exam_id = e.id
        JOIN {$this->users_table} u ON a.user_id = u.ID
        WHERE a.id = %d",
            $attempt_id
        ));

        if (!$attempt) {
            return '<div class="notice notice-error"><p>Attempt not found.</p></div>';
        }

        // Get full exam details with user answers and question data
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT 
            q.id,
            q.question_number,
            q.type,
            q.question_text,
            q.question_image,
            q.has_image,
            q.option_a,
            q.option_b,
            q.option_c,
            q.option_d,
            q.correct_answer,
            q.explanation,
            q.option_a_image,
            q.option_b_image,
            q.option_c_image,
            q.option_d_image,
            aa.selected_answer,
            aa.is_correct
        FROM {$wpdb->prefix}questions q
        LEFT JOIN {$wpdb->prefix}attempt_answers aa ON q.id = aa.question_id AND aa.attempt_id = %d
        WHERE q.exam_id = %d
        ORDER BY FIELD(q.id, {$attempt->question_order})",
            $attempt_id,
            $attempt->exam_id
        ));

        




        // Add back button
        $back_url = admin_url('admin.php?page=lwa-exams');
        $back_button = '<a href="' . esc_url($back_url) . '" class="button" style="margin-bottom: 20px;">&larr; Back to Dashboard</a>';

        ob_start();
        include LWA_EXAMS_PATH . 'frontend/templates/exam-results.php';
        $results_content = ob_get_clean();

        return '<div class="wrap">' . $results_content . '</div>';

        // return $back_button . $results_content;
    }

    public function get_attempts($filter = 0, $search = '')
    {
        global $wpdb;

        // Base query for getting attempts
        $query = "SELECT a.*, u.display_name, e.title 
                FROM {$this->attempts_table} a
                JOIN {$this->users_table} u ON a.user_id = u.ID
                JOIN {$this->exams_table} e ON a.exam_id = e.id";

        // Count query for pagination
        $count_query = "SELECT COUNT(*) 
                       FROM {$this->attempts_table} a
                       JOIN {$this->users_table} u ON a.user_id = u.ID
                       JOIN {$this->exams_table} e ON a.exam_id = e.id";

        $conditions = [];
        $params = [];

        // Handle user filter
        if ($filter > 0) {
            $conditions[] = 'a.user_id = %d';
            $params[] = intval($filter);
        }

        // Handle search query
        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($search)) . '%';
            $conditions[] = '(u.display_name LIKE %s OR e.title LIKE %s)';
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

        // Add ORDER BY and LIMIT to main query
        $query .= ' ORDER BY a.end_time DESC LIMIT %d OFFSET %d';

        // Get total count of items (for pagination)
        if (!empty($params)) {
            // For filtered queries, use prepare()
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $params));
        } else {
            // For unfiltered queries, query directly
            $total_items = $wpdb->get_var($count_query);
        }

        // Execute main query
        if (!empty($params)) {
            // For filtered queries, add LIMIT params to existing params
            $params[] = $per_page;
            $params[] = $offset;
            $attempts = $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            // For unfiltered queries, add LIMIT directly to query
            $query = str_replace(
                ['LIMIT %d OFFSET %d'],
                ["LIMIT {$per_page} OFFSET {$offset}"],
                $query
            );
            $attempts = $wpdb->get_results($query);
        }

        return [
            'items' => $attempts,
            'total_items' => $total_items,
            'per_page' => $per_page,
        ];
    }

    public function handle_delete_attempt()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'lwa_exams_delete_attempt')) {
            wp_die('Security check failed');
        }

        $id = intval($_GET['id']);
        $this->delete_attempt($id);

        wp_redirect(admin_url('admin.php?page=lwa-exams&message=' . urlencode(__('Attempt deleted successfully', 'lwa-exams'))));
        exit;
    }

    private function delete_attempt($attempt_id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->attempts_table,
            ['id' => $attempt_id],
            ['%d']
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $result;
    }

    public function handle_bulk_delete_attempts()
    {
        // Verify permissions
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        // Verify nonce
        check_admin_referer('bulk-attempts');

        // Check if bulk action is delete
        if (!isset($_POST['bulk_action']) || $_POST['bulk_action'] !== 'delete') {
            wp_redirect(admin_url('admin.php?page=lwa-exams'));
            exit;
        }

        // Check if categories were selected
        if (empty($_POST['attempt_ids'])) {
            wp_redirect(admin_url('admin.php?page=lwa-exams'));
            exit;
        }

        // Process deletions
        $deleted_count = 0;
        foreach ($_POST['attempt_ids'] as $attempt_id) {
            $result = $this->delete_attempt(intval($attempt_id));
            if ($result !== false) {
                $deleted_count++;
            }
        }

        // Set success message
        $message = sprintf(
            _n('%d attempt deleted successfully', '%d attempts deleted successfully', $deleted_count, 'lwa-exams'),
            $deleted_count
        );

        // Redirect with message
        wp_redirect(admin_url('admin.php?page=lwa-exams&message=' . urlencode($message)));
        exit;
    }



    public function lwa_exams_enqueue_assets($lwa_menu_hook)
    {
        // Only load on lwa plugin pages

        $allowed_pages = [
            'toplevel_page_lwa-exams',
            'lwa-exams_page_lwa-exams-exams',
            'lwa-exams_page_lwa-exams-categories',
            'lwa-exams_page_lwa-exams-questions',
        ];

        if (!in_array($lwa_menu_hook, $allowed_pages)) {
            return;
        }

        $lwa_admin_style = LWA_EXAMS_PATH . 'assets/css/admin-style.css';
        wp_enqueue_style(
            'lwa-admin-style',
            LWA_EXAMS_URL . 'assets/css/admin-style.css',
            [],
            filemtime($lwa_admin_style)
        );

        $lwa_admin_script = LWA_EXAMS_PATH . 'assets/js/admin-script.js';
        wp_enqueue_script(
            'lwa-admin-script',
            LWA_EXAMS_URL . 'assets/js/admin-script.js',
            ['jquery'],
            filemtime($lwa_admin_script),
            true
        );

        // ✅ Enqueue Select2 (from CDN)
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
    }
}
