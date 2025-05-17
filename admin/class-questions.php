<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

class LWA_EXAMS_Questions
{
    private $exams_table;
    private $questions_table;

    public function __construct()
    {
        global $wpdb;
        $this->exams_table = $wpdb->prefix . 'exams';
        $this->questions_table = $wpdb->prefix . 'questions';

        add_action('admin_post_lwa_exams_save_question', [$this, 'handle_form_submissions']);
        add_action('admin_post_nopriv_lwa_exams_save_question', [$this, 'handle_no_privileges']);
        add_action('admin_post_lwa_exams_delete_question', [$this, 'handle_delete_question']);
        add_action('admin_post_lwa_exams_bulk_delete_questions', [$this, 'handle_bulk_delete_questions']);
        add_action('admin_post_nopriv_lwa_exams_bulk_delete_questions', [$this, 'handle_no_privileges']);
    }

    public function render_admin_questions_page()
    {
        if (isset($_GET['message'])) {
            $message = urldecode($_GET['message']);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        if ($error = get_transient('lwa_exams_question_error')) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            delete_transient('lwa_exams_question_error');
        }

        $action = isset($_GET['action_questions']) ? sanitize_text_field($_GET['action_questions']) : 'list';
        $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
        $question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (!$exam_id) {
            echo '<div class="notice notice-error"><p>' . esc_html__('No exam selected. Please select an exam first.', 'exam-platform') . '</p></div>';
            return;
        }

        $exam = $this->get_exam($exam_id);
        if (!$exam) {
            wp_die(__('Invalid exam ID', 'lwa-exams'));
        }

        if ($action === 'edit' && $question_id) {
            $question = $this->get_question($question_id, $exam_id);
            if (!$question) {
                wp_die(__('Question not found', 'lwa-exams'));
            }
        }

        $filter = isset($_GET['question_type_filter']) ? sanitize_text_field($_GET['question_type_filter']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $questions = $this->get_questions($exam_id, $filter, $search);
        include LWA_EXAMS_PATH . 'admin/views/questions.php';
    }

    public function handle_form_submissions()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        if (!isset($_POST['lwa_exams_questions_nonce']) || !wp_verify_nonce($_POST['lwa_exams_questions_nonce'], 'lwa_exams_questions')) {
            wp_die('Security check failed');
        }

        $this->process_question_form();
    }

    private function handle_file_upload($file_input_name, $existing_value = '')
    {
        // Check for delete request, even if no new file is uploaded
        if (!empty($_POST['delete_' . $file_input_name])) {
            if (!empty($existing_value)) {
                $this->delete_attachment($existing_value);
            }
            return '';
        }

        // Process new file upload
        if (!empty($_FILES[$file_input_name]['name'])) {
            $attachment_id = media_handle_upload($file_input_name, 0); // 0 = not attached to any post

            if (!is_wp_error($attachment_id)) {
                // If existing image, delete it from Media Library
                if (!empty($existing_value)) {
                    $this->delete_attachment($existing_value);
                }

                return wp_get_attachment_url($attachment_id);
            }
        }

        // No change; keep existing image
        return $existing_value;
    }




    private function delete_attachment($file_url)
    {
        global $wpdb;

        // Find the attachment ID from the URL
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
            '%' . basename($file_url)
        ));

        if ($attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
    }

    private function get_question_image_url($question_id)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT question_image FROM {$this->questions_table} WHERE id = %d",
            $question_id
        ));
    }


    private function handle_option_images($options, $question_id = 0)
    {
        $option_images = [];

        foreach ($options as $option) {
            $existing_image = '';
            if ($question_id) {
                $existing_image = $this->get_option_image_url($question_id, $option);
            }

            $option_images[$option] = $this->handle_file_upload(
                'option_' . $option . '_image',
                $existing_image
            );
        }

        return $option_images;
    }

    // Add this new helper method to count filled options
    private function count_filled_options($options, $option_images)
    {
        $filled_options = 0;
        foreach ($options as $opt => $val) {
            if (!empty($val) || !empty($option_images[$opt])) {
                $filled_options++;
            }
        }
        return $filled_options;
    }

    private function sanitize_options($post_data, $options)
    {
        $sanitized = [];
        foreach ($options as $option) {
            $sanitized[$option] = sanitize_text_field($post_data['option_' . $option]);
        }
        return $sanitized;
    }

    private function get_option_image_url($question_id, $option)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT option_{$option}_image FROM {$this->questions_table} WHERE id = %d",
            $question_id
        ));
    }


    private function process_question_form()
    {
        global $wpdb;

        try {
            // Sanitize input data
            $exam_id = intval($_POST['exam_id']);
            $question_number = intval($_POST['question_number']);
            $question_text = sanitize_textarea_field($_POST['question_text']);
            $type = sanitize_text_field($_POST['type']);
            $has_image = isset($_POST['has_image']) ? 1 : 0;

            // Handle file uploads
            $question_image_url = $this->handle_file_upload('question_image', isset($_POST['id']) ? $this->get_question_image_url(intval($_POST['id'])) : '');

            // Initialize options and option images
            $options = [];
            $option_images = [];

            // Handle correct answer based on question type
            switch ($type) {
                case 'Multiple Select':
                    $correct_answers = isset($_POST['correct_answers']) ? $_POST['correct_answers'] : [];
                    if (empty($correct_answers)) {
                        throw new Exception(__('Please select at least one correct answer for multiple select question', 'lwa-exams'));
                    }
                    $correct_answer = implode(',', array_map('sanitize_text_field', $correct_answers));

                    // Process options for Multiple Select
                    $options = $this->sanitize_options($_POST, ['a', 'b', 'c', 'd']);
                    // error_log('Multiple Select options: ' . print_r($options, true));
                    $option_images = $this->handle_option_images(['a', 'b', 'c', 'd'], isset($_POST['id']) ? intval($_POST['id']) : 0);

                    // Validate at least two options are provided (text or image)
                    $filled_options = $this->count_filled_options($options, $option_images);
                    if ($filled_options < 2) {
                        throw new Exception(__('Please provide at least two options (text or image) for multiple select question', 'lwa-exams'));
                    }
                    break;

                case 'True/False':
                    if (!isset($_POST['correct_answer_tf'])) {
                        throw new Exception(__('Please select the correct answer for true/false question', 'lwa-exams'));
                    }
                    $correct_answer = sanitize_text_field($_POST['correct_answer_tf']);
                    $options = array_fill_keys(['a', 'b', 'c', 'd'], '');
                    $option_images = array_fill_keys(['a', 'b', 'c', 'd'], '');
                    break;

                case 'Fill in the Blank':
                    if (empty($_POST['correct_answer_fib'])) {
                        throw new Exception(__('Please enter the correct answer for fill in the blank question', 'lwa-exams'));
                    }
                    // Convert array to comma-separated string if multiple answers provided
                    $correct_answer = is_array($_POST['correct_answer_fib']) ?
                        implode(',', array_map('sanitize_text_field', $_POST['correct_answer_fib'])) :
                        sanitize_text_field($_POST['correct_answer_fib']);
                    $options = array_fill_keys(['a', 'b', 'c', 'd'], '');
                    $option_images = array_fill_keys(['a', 'b', 'c', 'd'], '');
                    break;

                case 'Multiple Choice': // Multiple Choice
                    if (empty($_POST['correct_answer_mc'])) {
                        throw new Exception(__('Please select the correct answer for multiple choice question', 'lwa-exams'));
                    }
                    $correct_answer = sanitize_text_field($_POST['correct_answer_mc']);

                    // Process options for Multiple Choice
                    $options = $this->sanitize_options($_POST, ['a', 'b', 'c', 'd']);
                    // error_log('Multiple Choice options: ' . print_r($options, true));
                    $option_images = $this->handle_option_images(['a', 'b', 'c', 'd'], isset($_POST['id']) ? intval($_POST['id']) : 0);


                    // Validate at least two options are provided (text or image)
                    $filled_options = $this->count_filled_options($options, $option_images);
                    if ($filled_options < 2) {
                        throw new Exception(__('Please provide at least two options (text or image) for multiple choice question', 'lwa-exams'));
                    }
                    break;

                default:
                    throw new Exception(__('Invalid question type provided.', 'lwa-exams'));
            }

            $explanation = sanitize_textarea_field($_POST['explanation']);
            $action = sanitize_text_field($_POST['action_questions']);

            if ($action === 'create_question') {
                $question_id = $this->create_question(
                    $exam_id,
                    $question_number,
                    $question_text,
                    $type,
                    $has_image,
                    $question_image_url,
                    $option_images,
                    $options,
                    $correct_answer,
                    $explanation
                );
                $message = __('Question added successfully', 'lwa-exams');
            } elseif ($action === 'update_question' && isset($_POST['id'])) {
                $question_id = intval($_POST['id']);
                $this->update_question(
                    $question_id,
                    $exam_id,
                    $question_number,
                    $question_text,
                    $type,
                    $has_image,
                    $question_image_url,
                    $option_images,
                    $options,
                    $correct_answer,
                    $explanation
                );
                $message = __('Question updated successfully', 'lwa-exams');
            }

            if (isset($question_id) && $question_id) {
                wp_redirect(admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $exam_id . '&message=' . urlencode($message)));
                exit;
            }

            throw new Exception(__('Failed to process question form', 'lwa-exams'));
        } catch (Exception $e) {
            set_transient('lwa_exams_question_error', $e->getMessage(), 45);
            $redirect_url = admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $exam_id);

            if (isset($_POST['id'])) {
                $redirect_url .= '&action_questions=edit&id=' . intval($_POST['id']);
            } else {
                $redirect_url .= '&action_questions=add';
            }

            wp_redirect($redirect_url);
            exit;
        }
    }



    private function create_question($exam_id, $question_number, $question_text, $type, $has_image, $question_image_url, $option_images, $options, $correct_answer, $explanation)
    {
        global $wpdb;

        $data = [
            'exam_id' => $exam_id,
            'question_number' => $question_number,
            'question_text' => $question_text,
            'type' => $type,
            'has_image' => $has_image,
            'question_image' => $question_image_url,
            'option_a_image' => $option_images['a'],
            'option_b_image' => $option_images['b'],
            'option_c_image' => $option_images['c'],
            'option_d_image' => $option_images['d'],
            'correct_answer' => $correct_answer,
            'explanation' => $explanation,
            'option_a' => $options['a'],
            'option_b' => $options['b'],
            'option_c' => $options['c'],
            'option_d' => $options['d']
        ];

        $result = $wpdb->insert($this->questions_table, $data);

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $wpdb->insert_id;
    }

    private function update_question($question_id, $exam_id, $question_number, $question_text, $type, $has_image, $question_image_url, $option_images, $options, $correct_answer, $explanation)
    {
        global $wpdb;

        $data = [
            'question_number' => $question_number,
            'question_text' => $question_text,
            'type' => $type,
            'has_image' => $has_image,
            'question_image' => $question_image_url,
            'option_a_image' => $option_images['a'],
            'option_b_image' => $option_images['b'],
            'option_c_image' => $option_images['c'],
            'option_d_image' => $option_images['d'],
            'correct_answer' => $correct_answer,
            'explanation' => $explanation,
            'option_a' => $options['a'],
            'option_b' => $options['b'],
            'option_c' => $options['c'],
            'option_d' => $options['d']
        ];

        $result = $wpdb->update(
            $this->questions_table,
            $data,
            ['id' => $question_id, 'exam_id' => $exam_id]
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $result;
    }

    public function handle_delete_question()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'lwa_exams_delete_question')) {
            wp_die('Security check failed');
        }

        $id = intval($_GET['id']);
        $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
        $this->delete_question($id);

        wp_redirect(admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $exam_id . '&message=' . urlencode(__('Question deleted successfully', 'lwa-exams'))));
        exit;
    }

    public function handle_bulk_delete_questions()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bulk-questions')) {
            wp_die('Security check failed');
        }

        if (!isset($_POST['bulk_action']) || $_POST['bulk_action'] !== 'delete') {
            wp_redirect(admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $_POST['exam_id']));
            exit;
        }

        if (empty($_POST['question_ids'])) {
            wp_redirect(admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $_POST['exam_id']));
            exit;
        }

        $deleted_count = 0;
        foreach ($_POST['question_ids'] as $question_id) {
            $result = $this->delete_question(intval($question_id));
            if ($result !== false) {
                $deleted_count++;
            }
        }

        $message = sprintf(
            _n('%d question deleted successfully', '%d questions deleted successfully', $deleted_count, 'lwa-exams'),
            $deleted_count
        );

        wp_redirect(admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $_POST['exam_id'] . '&message=' . urlencode($message)));
        exit;
    }

    private function delete_question($question_id)
    {
        global $wpdb;

        // First get the question to delete associated files
        $question = $this->get_question($question_id, 0); // 0 to skip exam_id check

        if ($question) {
            // Delete question image if exists
            if (!empty($question->question_image)) {
                $this->delete_attachment($question->question_image);
            }

            // Delete option images if they exist
            foreach (['a', 'b', 'c', 'd'] as $option) {
                $image_field = 'option_' . $option . '_image';
                if (!empty($question->$image_field)) {
                    $this->delete_attachment($question->$image_field);
                }
            }
        }

        $result = $wpdb->delete(
            $this->questions_table,
            ['id' => $question_id],
            ['%d']
        );

        if (false === $result) {
            throw new Exception($wpdb->last_error);
        }

        return $result;
    }

    private function get_exam($exam_id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->exams_table} WHERE id = %d",
            $exam_id
        ));
    }

    private function get_question($question_id, $exam_id)
    {
        global $wpdb;
        $query = "SELECT * FROM {$this->questions_table} WHERE id = %d";
        $params = [$question_id];

        if ($exam_id) {
            $query .= " AND exam_id = %d";
            $params[] = $exam_id;
        }

        return $wpdb->get_row($wpdb->prepare($query, $params));
    }

    public function get_questions($exam_id, $filter = 0, $search = '')
    {
        global $wpdb;

        // Base query
        $query = "SELECT * FROM {$this->questions_table}";

        // Initialize conditions and parameters
        $conditions = [];
        $params = [];

        // Always filter by exam_id
        $conditions[] = 'exam_id = %d';
        $params[] = intval($exam_id);

        // Handle question type filter
        if (!empty($filter) && $filter !== '0') {
            $conditions[] = 'type = %s';
            $params[] = sanitize_text_field($filter);
        }

        // Handle search query
        if (!empty($search)) {
            $search_term = '%' . $wpdb->esc_like(sanitize_text_field($search)) . '%';
            $conditions[] = '(question_text LIKE %s OR type LIKE %s)';
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Add WHERE clause if conditions exist
        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        // Add ORDER BY
        $query .= ' ORDER BY question_number';

        // Prepare and execute the query
        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        } else {
            return $wpdb->get_results($query);
        }
    }

    public function handle_no_privileges()
    {
        wp_die('You must be logged in to submit this form.');
    }
}
