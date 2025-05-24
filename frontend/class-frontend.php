<?php
if (!defined('ABSPATH')) {
    exit;
}

class LWA_EXAMS_Frontend
{
    private static $instance = null;
    private $attempt_id = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {

        // Start session if not already started
        if (!session_id()) {
            session_start();
        }

        // Verify WordPress authentication consistency
        add_action('template_redirect', [$this, 'verify_authentication'], 1);


        // Register shortcodes
        add_shortcode('lwa_exams_list', [$this, 'render_exam_list']);
        add_shortcode('lwa_exam', [$this, 'render_exam_interface']);
        add_shortcode('lwa_attempts', [$this, 'render_user_attempts']);

        // Add noindex meta tag for private pages
        add_action('wp_head', [$this, 'add_noindex_meta']);

        // Handle form submissions
        add_action('wp_ajax_lwa_start_exam', [$this, 'handle_start_exam']);
        add_action('wp_ajax_nopriv_lwa_start_exam', [$this, 'handle_no_privileges']);

        add_action('wp_ajax_lwa_submit_exam', [$this, 'handle_submit_exam']);
        add_action('wp_ajax_nopriv_lwa_submit_exam', [$this, 'handle_no_privileges']);

        add_action('wp_ajax_lwa_get_remaining_time', [$this, 'handle_get_remaining_time']);
        add_action('wp_ajax_nopriv_lwa_get_remaining_time', [$this, 'handle_no_privileges']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_noindex_meta()
    {
        global $post;

        if (
            is_a($post, 'WP_Post') &&
            (has_shortcode($post->post_content, 'lwa_exam') ||
                has_shortcode($post->post_content, 'lwa_attempts'))
        ) {
            echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
        }
    }


    public function verify_authentication()
    {
        global $post;

        $require_auth = apply_filters('lwa_exams_require_auth', true);
        if (!$require_auth) return;

        // Only check on our plugin pages
        if (is_a($post, 'WP_Post') && (

            has_shortcode($post->post_content, 'lwa_exam') ||
            has_shortcode($post->post_content, 'lwa_attempts')
        )) {
            $this->check_authentication();
        }
    }


    public function enqueue_assets()
    {
        // Only load on pages with our shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'lwa_exams_list') ||
            has_shortcode($post->post_content, 'lwa_exam') ||
            has_shortcode($post->post_content, 'lwa_attempts')
        )) {
            wp_enqueue_style(
                'lwa-frontend-style',
                LWA_EXAMS_URL . 'assets/css/frontend-style.css',
                [],
                filemtime(LWA_EXAMS_PATH . 'assets/css/frontend-style.css')
            );

            wp_enqueue_script(
                'lwa-frontend-script',
                LWA_EXAMS_URL . 'assets/js/frontend-script.js',
                ['jquery'],
                filemtime(LWA_EXAMS_PATH . 'assets/js/frontend-script.js'),
                true
            );

            wp_localize_script('lwa-frontend-script', 'lwa_exams', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lwa_exams_nonce'),
                'is_user_logged_in' => is_user_logged_in()
            ]);
        }
    }

    public function render_exam_list($atts)
    {

        // Prevent caching for logged-in users
        if (is_user_logged_in()) {
            nocache_headers();
        }

        global $wpdb;
        $exams_table = $wpdb->prefix . 'exams';
        $attempts_table = $wpdb->prefix . 'attempts';

        $user_id = get_current_user_id();

        // Get all active exams
        $exams = $wpdb->get_results(
            "SELECT e.*, 
            (SELECT COUNT(*) FROM {$wpdb->prefix}questions WHERE exam_id = e.id) as question_count,
            (SELECT COUNT(*) FROM {$attempts_table} WHERE exam_id = e.id AND user_id = {$user_id}) as attempts_count
            FROM {$exams_table} e 
            WHERE e.is_active = 1
            ORDER BY e.id DESC"
        );

        if (empty($exams)) {
            return '<p>No exams available at this time.</p>';
        }

        ob_start();
        include LWA_EXAMS_PATH . 'frontend/templates/exam-list.php';
        return ob_get_clean();
    }


    public function handle_get_remaining_time()
    {
        check_ajax_referer('lwa_exams_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in.');
        }

        $attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;

        global $wpdb;
        $user_id = get_current_user_id();

        // Verify attempt exists and is in progress
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, e.time_limit_minutes 
        FROM {$wpdb->prefix}attempts a
        JOIN {$wpdb->prefix}exams e ON a.exam_id = e.id
        WHERE a.id = %d AND a.user_id = %d AND a.status = 'in_progress'",
            $attempt_id,
            $user_id
        ));

        if (!$attempt) {
            wp_send_json_error('Invalid attempt.');
        }

        $current_time = current_time('timestamp');
        $start_time = strtotime($attempt->start_time);
        $time_limit_seconds = $attempt->time_limit_minutes * 60;
        $elapsed_time = $current_time - $start_time;
        $remaining_time = max(0, $time_limit_seconds - $elapsed_time);

        wp_send_json_success([
            'remaining_time' => $remaining_time
        ]);
    }



    public function render_exam_interface($atts)
    {

        $this->check_authentication();

        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

        if (!$exam_id) {
            return '<p>No exam selected.</p>';
        }

        global $wpdb;
        $exam = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}exams WHERE id = %d AND is_active = 1",
            $exam_id
        ));

        if (!$exam) {
            return '<p>Exam not found or not available.</p>';
        }

        // Check if we're starting, in progress, or showing results
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'start':
                    return $this->render_exam_start($exam);
                case 'questions':
                    return $this->render_exam_questions($exam);
                case 'results':
                    return $this->render_exam_results($exam);
                default:
                    return $this->render_exam_start($exam);
            }
        }

        return $this->render_exam_start($exam);
    }

    protected function render_exam_start($exam)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        // Check for existing incomplete attempt
        $incomplete_attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}attempts 
            WHERE exam_id = %d AND user_id = %d AND status = 'in_progress'",
            $exam->id,
            $user_id
        ));

        $exam->question_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}questions WHERE exam_id = %d",
            $exam->id
        ));


        ob_start();
        include LWA_EXAMS_PATH . 'frontend/templates/exam-start.php';
        return ob_get_clean();
    }

    protected function render_exam_questions($exam)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        // Verify attempt
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}attempts 
            WHERE id = %d AND exam_id = %d AND user_id = %d AND status = 'in_progress'",
            intval($_GET['attempt_id']),
            $exam->id,
            $user_id
        ));

        if (!$attempt) {
            return '<p>Invalid attempt. Please start the exam again.</p>';
        }

        // After getting the attempt, calculate the remaining time
        $current_time = current_time('timestamp');
        $start_time = strtotime($attempt->start_time);
        $time_limit_seconds = $exam->time_limit_minutes * 60;
        $elapsed_time = $current_time - $start_time;
        $remaining_time = max(0, $time_limit_seconds - $elapsed_time);

        // Pass this to the template
        $timer_data = [
            'remaining_time' => $remaining_time,
            'time_limit' => $time_limit_seconds,
            'start_time' => $attempt->start_time
        ];

        // Get questions
        // Get questions IN THE SAVED ORDER
        $questions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}questions 
        WHERE id IN ({$attempt->question_order})
        ORDER BY FIELD(id, {$attempt->question_order})"
        );

        if (empty($questions)) {
            return '<p>No questions found for this exam.</p>';
        }

        $this->attempt_id = $attempt->id;

        ob_start();
        include LWA_EXAMS_PATH . 'frontend/templates/exam-questions.php';
        return ob_get_clean();
    }

    protected function render_exam_results($exam)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        // Verify attempt
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, e.title, e.passing_score 
            FROM {$wpdb->prefix}attempts a
            JOIN {$wpdb->prefix}exams e ON a.exam_id = e.id
            WHERE a.id = %d AND a.exam_id = %d AND a.user_id = %d",
            intval($_GET['attempt_id']),
            $exam->id,
            $user_id
        ));

        if (!$attempt) {
            return '<p>Results not found.</p>';
        }

        // Get the attempt's question order:
        // $order = explode(',', $attempt->question_order); // [5, 2, 9, 1]

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
            $attempt->id,
            $exam->id

        ));

        ob_start();
        include LWA_EXAMS_PATH . 'frontend/templates/exam-results.php';
        return ob_get_clean();
    }

    public function render_user_attempts($atts)
    {

        $this->check_authentication();

        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        global $wpdb;
        $user_id = get_current_user_id();

        $attempts = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, e.title, e.passing_score 
            FROM {$wpdb->prefix}attempts a
            JOIN {$wpdb->prefix}exams e ON a.exam_id = e.id
            WHERE a.user_id = %d
            ORDER BY a.start_time DESC",
            $user_id
        ));

        ob_start();
        include LWA_EXAMS_PATH . 'frontend/templates/user-attempts.php';
        return ob_get_clean();
    }

    public function handle_start_exam()
    {
        check_ajax_referer('lwa_exams_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in to start an exam.');
        }

        // Get current user ID from WordPress rather than POST data
        $user_id = get_current_user_id();

        $exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : 0;

        global $wpdb;

        // Check if exam exists and is active
        $exam = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}exams WHERE id = %d AND is_active = 1",
            $exam_id
        ));

        if (!$exam) {
            wp_send_json_error('Exam not found or not available.');
        }

        // Fetch questions in RANDOM order
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}questions WHERE exam_id = %d ORDER BY RAND()",
            $exam_id
        ));

        $question_order = implode(',', wp_list_pluck($questions, 'id'));

        // Create new attempt
        $wpdb->insert(
            $wpdb->prefix . 'attempts',
            [
                'user_id'    => $user_id,
                'exam_id'    => $exam_id,
                'start_time' => current_time('mysql'),
                'status'     => 'in_progress',
                'question_order' => $question_order
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        $attempt_id = $wpdb->insert_id;

        wp_send_json_success([
            'attempt_id' => $attempt_id,
            'questions' => $questions
        ]);
    }

    public function handle_submit_exam()
    {
        // Verify required data
        if (!isset($_POST['attempt_id']) || !isset($_POST['answers'])) {
            wp_send_json_error('Invalid submission data.');
        }

        // Validate attempt ID
        $attempt_id = intval($_POST['attempt_id']);
        if ($attempt_id <= 0) {
            wp_send_json_error('Invalid attempt ID.');
        }

        // Security checks
        check_ajax_referer('lwa_exams_nonce', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in to submit an exam.');
        }

        global $wpdb;
        $user_id = get_current_user_id();

        // Verify attempt exists and is in progress
        $attempt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}attempts 
        WHERE id = %d AND user_id = %d AND status = 'in_progress'",
            $attempt_id,
            $user_id
        ));

        if (!$attempt) {
            wp_send_json_error('Invalid attempt.');
        }

        // Get exam details
        $exam = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}exams WHERE id = %d",
            $attempt->exam_id
        ));

        // Get all questions with their types and correct answers
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, type, correct_answer FROM {$wpdb->prefix}questions 
        WHERE exam_id = %d",
            $exam->id
        ));

        $total_questions = count($questions);
        $correct_answers = 0;

        // Process each answer
        foreach ($questions as $question) {
            $selected_answer = isset($_POST['answers'][$question->id]) ? $_POST['answers'][$question->id] : '';
            $is_correct = 0;

            // Handle different question types
            switch ($question->type) {
                case 'Multiple Select':
                    // Convert to array if not already
                    $user_answers = is_array($selected_answer) ? $selected_answer : [$selected_answer];
                    $correct_answers_array = explode(',', $question->correct_answer);

                    // Normalize all answers (trim + uppercase)
                    $user_answers = array_map(function ($a) {
                        return strtoupper(trim($a));
                    }, array_filter($user_answers));

                    $correct_answers_array = array_map(function ($a) {
                        return strtoupper(trim($a));
                    }, $correct_answers_array);

                    // Compare sorted arrays
                    sort($user_answers);
                    sort($correct_answers_array);

                    $is_correct = ($user_answers == $correct_answers_array) ? 1 : 0;
                    $selected_answer = implode(',', $user_answers);
                    break;

                case 'True/False':
                    // Ensure single answer is string
                    $selected_answer = is_array($selected_answer) ? $selected_answer[0] : $selected_answer;
                    $selected_answer = strtoupper(substr(trim($selected_answer ?? ''), 0, 1));
                    $correct_answer = strtoupper(substr(trim($question->correct_answer), 0, 1));

                    $is_correct = ($selected_answer === $correct_answer) ? 1 : 0;
                    break;

                case 'Fill in the Blank':

                    // error_log('Processing question ID: ' . $question->id);
                    // error_log('Question type: ' . $question->type);
                    // error_log('Submitted answer: ' . print_r($selected_answer, true));
                    // error_log('Correct answer: ' . $question->correct_answer);
                    // Ensure we have an array of user answers
                    $user_answers = is_array($selected_answer) ? $selected_answer : [$selected_answer];

                    // Get correct answers as array
                    $correct_answers_array = !empty($question->correct_answer) ?
                        explode(',', $question->correct_answer) :
                        [];

                    // Initialize empty arrays if we have no answers
                    if (empty($user_answers)) {
                        $user_answers = array_fill(0, count($correct_answers_array), '');
                    }

                    // Normalize all answers (trim + lowercase)
                    $user_answers = array_map(function ($a) {
                        return !empty($a) ? strtolower(trim($a)) : '';
                    }, $user_answers);

                    $correct_answers_array = array_map(function ($a) {
                        return strtolower(trim($a));
                    }, $correct_answers_array);

                    // Ensure arrays have same length
                    $blank_count = max(count($user_answers), count($correct_answers_array));
                    $user_answers = array_pad($user_answers, $blank_count, '');
                    $correct_answers_array = array_pad($correct_answers_array, $blank_count, '');

                    // Compare arrays directly (order matters)
                    $is_correct = ($user_answers == $correct_answers_array) ? 1 : 0;

                    // Save answers as comma-separated string (empty string if all blanks empty)
                    $selected_answer = implode(',', $user_answers);
                    if (trim($selected_answer, ',') === '') {
                        $selected_answer = ''; // Store empty string instead of just commas
                    }
                    break;

                case 'Multiple Choice': // Multiple Choice
                    if (empty($selected_answer)) {
                        $is_correct = 0;
                        break;
                    }
                    // Ensure single answer is string
                    $selected_answer = is_array($selected_answer) ? $selected_answer[0] : $selected_answer;
                    $selected_answer = strtoupper(trim($selected_answer ?? ''));
                    $correct_answer = strtoupper(trim($question->correct_answer));

                    $is_correct = ($selected_answer === $correct_answer) ? 1 : 0;
                    break;
            }

            // error_log('Is Correct: ' . $is_correct);

            // Save answer
            $wpdb->insert(
                $wpdb->prefix . 'attempt_answers',
                [
                    'attempt_id' => $attempt_id,
                    'question_id' => $question->id,
                    'selected_answer' => $selected_answer,
                    'is_correct' => $is_correct
                ],
                ['%d', '%d', '%s', '%d']
            );

            if ($is_correct) {
                $correct_answers++;
            }
        }

        // Calculate final score
        $score = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100) : 0;
        $passed = $score >= $exam->passing_score ? 1 : 0;

        // Update attempt record
        $wpdb->update(
            $wpdb->prefix . 'attempts',
            [
                'score' => $correct_answers,
                'total_questions' => $total_questions,
                'percentage' => $score,
                'passed' => $passed,
                'end_time' => current_time('mysql'),
                'status' => 'completed',
                'time_taken_seconds' => time() - strtotime($attempt->start_time)
            ],
            ['id' => $attempt_id],
            ['%d', '%d', '%d', '%d', '%s', '%s', '%d'],
            ['%d']
        );

        // Return success with attempt ID
        wp_send_json_success([
            'attempt_id' => $attempt_id
        ]);
    }

    // protected function render_login_message()
    // {
    //     return '<div class="lwa-login-required">' .
    //     '<h1>Login to Your Account</h1>' .
    //     wp_login_form(['echo' => false]) .
    //     '</div>';
    // }

    protected function render_login_message()
    {
        // Store the current URL for redirect after login
        if (!isset($_GET['redirect_to'])) {
            $redirect_url = $_SERVER['REQUEST_URI'];
            wp_redirect(wp_login_url($redirect_url));
            exit;
        }
        return '';
    }

    // Add this at the top of the class
    private function check_authentication()
    {
        if (!is_user_logged_in()) {
            auth_redirect(); // WordPress built-in that properly handles redirects
            exit;
        }
        return true;
    }

    public function handle_no_privileges()
    {
        wp_send_json_error('You must be logged in to perform this action.');
    }
}

// Initialize the frontend
LWA_EXAMS_Frontend::get_instance();
