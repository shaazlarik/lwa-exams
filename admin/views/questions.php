<?php

if (!defined('ABSPATH')) {
    exit;
}

?>
<style>
    .image-preview {
        max-width: 150px;
        max-height: 100px;
        display: block;
        margin-top: 5px;
    }

    .option-image-container {
        margin-top: 5px;
    }

    .question-type-fields {
        display: none;
    }

    .option-container {
        margin-bottom: 15px;
        padding: 10px;
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .option-label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Questions for: ', 'lwa-exams') . esc_html($exam->title); ?>
        <a href="<?php echo admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $exam_id . '&action_questions=add'); ?>" class="page-title-action">
            <?php echo esc_html__('Add New', 'lwa-exams'); ?>
        </a>
    </h1>

    <?php if ($action === 'list') : ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="bulk-action-form">
            <input type="hidden" name="page" value="lwa-exams-questions">
            <input type="hidden" name="action" value="lwa_exams_bulk_delete_questions">
            <input type="hidden" name="exam_id" value="<?php echo esc_attr($exam_id); ?>">
            <?php wp_nonce_field('bulk-questions'); ?>

            <div class="tablenav top">

                <!-- Bulk Actions Dropdown -->
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'lwa-exams'); ?></label>
                    <select name="bulk_action" id="bulk-action-selector-top">
                        <option value="-1"><?php esc_html_e('Bulk actions', 'lwa-exams'); ?></option>
                        <option value="delete"><?php esc_html_e('Delete', 'lwa-exams'); ?></option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="<?php esc_attr_e('Apply', 'lwa-exams'); ?>">
                </div>

                <!-- Filter (GET form needs to be separate but we'll handle it with JavaScript) -->
                <div class="alignleft actions">
                    <label for="filter-by-question_type" class="screen-reader-text"><?php esc_html_e('Filter by question type', 'lwa-exams'); ?></label>
                    <select name="question_type_filter" id="filter-by-question_type">
                        <option value=""><?php esc_html_e('All Questions', 'lwa-exams'); ?></option>
                        <?php
                        // Get unique question types
                        $question_types = array_unique(wp_list_pluck($questions, 'type'));
                        foreach ($question_types as $type) : ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected(isset($_GET['question_type_filter']) ? $_GET['question_type_filter'] : '', $type); ?>>
                                <?php echo esc_html($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="filter-submit" class="button">
                        <?php echo !empty($_GET['question_type_filter']) ? esc_html__('Clear Filter', 'lwa-exams') : esc_html__('Filter', 'lwa-exams'); ?>
                    </button>
                </div>

                <!-- Search (GET form needs to be separate but we'll handle it with JavaScript) -->
                <div class="alignright actions">
                    <div class="search-form">
                        <label class="screen-reader-text" for="question-search-input"><?php esc_html_e('Search Question Types', 'lwa-exams'); ?></label>
                        <input type="search" id="question-search-input" name="s" value="<?php echo esc_attr(isset($_GET['s']) ? $_GET['s'] : ''); ?>">
                        <button type="button" id="search-submit" class="button">
                            <?php echo !empty($_GET['s']) ? esc_html__('Clear Search', 'lwa-exams') : esc_html__('Search Questions', 'lwa-exams'); ?>
                        </button>
                    </div>
                </div>
                <br class="clear">
            </div>

            <!-- Questions Table -->
            <table class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th scope="col" class="manage-column column-primary"><?php echo esc_html__('Question #', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Question Text', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Question Type', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Media', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Correct Answer', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Has Image', 'lwa-exams'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php foreach ($questions as $q) : ?>
                        <tr class="iedit">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="question_ids[]" value="<?php echo esc_attr($q->id); ?>">
                            </th>
                            <td class="has-row-actions column-primary" data-colname="Question #">
                                <strong><?php echo esc_html($q->question_number); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $exam_id . '&action_questions=edit&id=' . $q->id); ?>">
                                            <?php esc_html_e('Edit', 'lwa-exams'); ?>
                                        </a> |
                                    </span>
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=lwa_exams_delete_question&id=' . $q->id . '&exam_id=' . $exam_id), 'lwa_exams_delete_question'); ?>"
                                            onclick="return confirm('<?php esc_attr_e('Are you sure?', 'lwa-exams'); ?>')">
                                            <?php esc_html_e('Delete', 'lwa-exams'); ?>
                                        </a>
                                    </span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'lwa-exams'); ?></span></button>
                            </td>
                            <td data-colname="Question Text"><?php echo esc_html(substr($q->question_text, 0, 50)); ?></td>
                            <td data-colname="Question Type"><?php echo esc_html($q->type); ?></td>


                            <td>
                                <?php if (!empty($q->question_image)): ?>
                                    <img src="<?php echo esc_url($q->question_image); ?>" class="image-preview" style="width: 60px; height: auto; margin-right: 5px;" />

                                <?php else: ?>
                                    No Media
                                <?php endif; ?>
                            </td>

                            <td data-colname="Correct Answer"><?php echo esc_html($q->correct_answer); ?></td>
                            <td data-colname="Has Image">
                                <span class="category-badge <?php echo $q->has_image ? 'has_image' : 'no_image'; ?>">
                                    <?php echo esc_html($q->has_image) ? 'Has Image' : 'No Image'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <script>
            jQuery(document).ready(function($) {
                // Base URL with preserved parameters
                var base_url = '<?php echo admin_url('admin.php?page=lwa-exams-questions&exam_id=' . (isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0)); ?>';

                // Replace the filter handler in your JavaScript
                $('#filter-submit').on('click', function() {
                    var new_url = base_url;

                    if ($(this).text().trim() !== 'Clear Filter') {
                        var filterValue = $('#filter-by-question_type').val();
                        if (filterValue && filterValue !== '0') {
                            new_url += '&question_type_filter=' + encodeURIComponent(filterValue);
                        }
                    }

                    window.location.href = new_url;
                });

                // Handle search submit/clear
                $('#search-submit').on('click', function() {
                    var new_url = base_url;

                    if ($(this).text().trim() !== 'Clear Search') {
                        var searchValue = $('#question-search-input').val();
                        if (searchValue) new_url += '&s=' + encodeURIComponent(searchValue);
                    }

                    window.location.href = new_url;
                });

                // Enter key handler remains the same
                $('#question-search-input').on('keypress', function(e) {
                    if (e.which === 13) {
                        $('#search-submit').click();
                        return false;
                    }
                });
            });
        </script>

        <script>
            jQuery(document).ready(function($) {
                // Bulk delete confirmation
                $('#doaction').click(function(e) {
                    if ($('select[name="bulk_action"]').val() === '-1') {
                        alert('<?php esc_attr_e('Please select a bulk action', 'lwa-exams'); ?>');
                        return false;
                    }

                    if ($('select[name="bulk_action"]').val() === 'delete') {
                        if ($('tbody input[type="checkbox"]:checked').length === 0) {
                            alert('<?php esc_attr_e('Please select at least one question', 'lwa-exams'); ?>');
                            return false;
                        }
                        return confirm('<?php esc_attr_e('Are you sure you want to delete these questions?', 'lwa-exams'); ?>');
                    }
                });

                // Select all checkboxes
                $('#cb-select-all-1').click(function() {
                    $('tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
                });
            });
        </script>

    <?php else : ?>
        <h2><?php echo $action === 'add' ? esc_html__('Add New Question', 'lwa-exams') : esc_html__('Edit Question', 'lwa-exams'); ?></h2>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="lwa-exams-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="lwa_exams_save_question">
            <?php wp_nonce_field('lwa_exams_questions', 'lwa_exams_questions_nonce'); ?>
            <input type="hidden" name="action_questions" value="<?php echo $action === 'add' ? 'create_question' : 'update_question'; ?>">
            <input type="hidden" name="exam_id" value="<?php echo esc_attr($exam_id); ?>">

            <?php if ($action === 'edit') : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($question->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="question_number"><?php esc_html_e('Question Number', 'lwa-exams'); ?></label></th>
                    <td>
                        <input type="number" name="question_number" id="question_number" min="1"
                            value="<?php echo isset($question) ? esc_attr($question->question_number) : (count($questions) + 1); ?>" required>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="question_text"><?php esc_html_e('Question Text', 'lwa-exams'); ?></label></th>
                    <td>
                        <textarea name="question_text" id="question_text" rows="3" class="large-text" required><?php
                                                                                                                echo isset($question) ? esc_textarea($question->question_text) : '';
                                                                                                                ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="question_image"><?php esc_html_e('Question Image', 'lwa-exams'); ?></label></th>
                    <td>
                        <input type="file" name="question_image" id="question_image">
                        <?php if (isset($question) && !empty($question->question_image)) : ?>
                            <img src="<?php echo esc_url($question->question_image); ?>" class="image-preview">
                            <label>
                                <input type="checkbox" name="delete_question_image" value="1">
                                <?php esc_html_e('Delete image', 'lwa-exams'); ?>
                            </label>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="has_image"><?php esc_html_e('Has Image', 'lwa-exams'); ?></label></th>
                    <td>
                        <input type="checkbox" name="has_image" id="has_image" value="1" <?php echo (isset($question) && $question->has_image ? 'checked' : ''); ?>>
                        <label for="has_image"><?php esc_html_e('This question includes an image', 'lwa-exams'); ?></label>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="type"><?php esc_html_e('Question Type', 'lwa-exams'); ?></label></th>
                    <td>
                        <select name="type" id="type" required>
                            <option value="" disabled selected><?php esc_html_e('Please select a question type', 'lwa-exams'); ?></option>
                            <option value="Multiple Choice" <?php selected(isset($question) && $question->type === 'Multiple Choice'); ?>>
                                <?php esc_html_e('Multiple Choice', 'lwa-exams'); ?>
                            </option>
                            <option value="True/False" <?php selected(isset($question) && $question->type === 'True/False'); ?>>
                                <?php esc_html_e('True/False', 'lwa-exams'); ?>
                            </option>
                            <option value="Multiple Select" <?php selected(isset($question) && $question->type === 'Multiple Select'); ?>>
                                <?php esc_html_e('Multiple Select', 'lwa-exams'); ?>
                            </option>
                            <option value="Fill in the Blank" <?php selected(isset($question) && $question->type === 'Fill in the Blank'); ?>>
                                <?php esc_html_e('Fill in the Blank', 'lwa-exams'); ?>
                            </option>
                        </select>
                    </td>
                </tr>

                <!-- Multiple Choice Fields -->
                <tbody class="question-type-fields multiple-choice-fields">
                    <tr>
                        <th scope="row"><label><?php esc_html_e('Options', 'lwa-exams'); ?></label></th>
                        <td>
                            <?php foreach (['a', 'b', 'c', 'd'] as $option) : ?>
                                <div class="option-container">
                                    <span class="option-label"><?php echo esc_html(ucfirst($option)); ?></span>

                                    <div class="option-text-container">
                                        <label for="option_<?php echo esc_attr($option); ?>_text"><?php esc_html_e('Text Option', 'lwa-exams'); ?></label>
                                        <input type="text" name="option_<?php echo esc_attr($option); ?>"
                                            id="option_<?php echo esc_attr($option); ?>_text"
                                            class="regular-text"
                                            value="<?php echo isset($question) ? esc_attr($question->{'option_' . $option}) : ''; ?>">
                                    </div>

                                    <div class="option-image-container">
                                        <label for="option_<?php echo esc_attr($option); ?>_image"><?php esc_html_e('Image Option', 'lwa-exams'); ?></label>
                                        <input type="file" name="option_<?php echo esc_attr($option); ?>_image">
                                        <?php if (isset($question) && !empty($question->{'option_' . $option . '_image'})) : ?>
                                            <img src="<?php echo esc_url($question->{'option_' . $option . '_image'}); ?>" class="image-preview" data-option-filled="1">
                                            <label>
                                                <input type="checkbox" name="delete_option_<?php echo esc_attr($option); ?>_image" value="1">
                                                <?php esc_html_e('Delete image', 'lwa-exams'); ?>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="correct_answer_mc"><?php esc_html_e('Correct Answer', 'lwa-exams'); ?></label></th>
                        <td>
                            <select name="correct_answer_mc" id="correct_answer_mc">
                                <option value=""><?php esc_html_e('Select correct answer', 'lwa-exams'); ?></option>
                                <?php foreach (['A', 'B', 'C', 'D'] as $option) : ?>
                                    <option value="<?php echo esc_attr($option); ?>" <?php
                                                                                        echo (isset($question) && $question->type === 'Multiple Choice' && $question->correct_answer === $option ? 'selected' : '');
                                                                                        ?>>
                                        <?php echo esc_html($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </tbody>

                <!-- Multiple Select Fields -->
                <tbody class="question-type-fields multiple-select-fields">
                    <tr>
                        <th scope="row"><label><?php esc_html_e('Options', 'lwa-exams'); ?></label></th>
                        <td>
                            <?php foreach (['a', 'b', 'c', 'd'] as $option) : ?>
                                <div class="option-container">
                                    <span class="option-label"><?php echo esc_html(ucfirst($option)); ?></span>

                                    <div class="option-text-container">
                                        <label for="option_<?php echo esc_attr($option); ?>_text"><?php esc_html_e('Text Option', 'lwa-exams'); ?></label>
                                        <input type="text" name="option_<?php echo esc_attr($option); ?>"
                                            id="option_<?php echo esc_attr($option); ?>_text"
                                            class="regular-text"
                                            value="<?php echo isset($question) ? esc_attr($question->{'option_' . $option}) : ''; ?>">
                                    </div>

                                    <div class="option-image-container">
                                        <label for="option_<?php echo esc_attr($option); ?>_image"><?php esc_html_e('Image Option', 'lwa-exams'); ?></label>
                                        <input type="file" name="option_<?php echo esc_attr($option); ?>_image">
                                        <?php if (isset($question) && !empty($question->{'option_' . $option . '_image'})) : ?>
                                            <img src="<?php echo esc_url($question->{'option_' . $option . '_image'}); ?>" class="image-preview" data-option-filled="1">
                                            <label>
                                                <input type="checkbox" name="delete_option_<?php echo esc_attr($option); ?>_image" value="1">
                                                <?php esc_html_e('Delete image', 'lwa-exams'); ?>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Correct Answers', 'lwa-exams'); ?></th>
                        <td>
                            <?php
                            $correct_answers = isset($question) && $question->type === 'Multiple Select' ? explode(',', $question->correct_answer) : [];
                            foreach (['A', 'B', 'C', 'D'] as $option) : ?>
                                <label>
                                    <input type="checkbox" name="correct_answers[]" value="<?php echo esc_attr($option); ?>"
                                        <?php checked(in_array($option, $correct_answers)); ?>>
                                    <?php echo esc_html($option); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </tbody>

                <!-- True/False Fields -->
                <tbody class="question-type-fields true-false-fields">
                    <tr>
                        <th scope="row"><?php esc_html_e('Correct Answer', 'lwa-exams'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="correct_answer_tf" value="True"
                                    <?php checked(isset($question) && $question->type === 'True/False' && $question->correct_answer === 'True'); ?>>
                                <?php esc_html_e('True', 'lwa-exams'); ?>
                            </label>
                            <label>
                                <input type="radio" name="correct_answer_tf" value="False"
                                    <?php checked(isset($question) && $question->type === 'True/False' && $question->correct_answer === 'False'); ?>>
                                <?php esc_html_e('False', 'lwa-exams'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>

                <!-- Fill in the Blank Fields -->
                <!-- Replace the Fill in the Blank section in questions.php with: -->
                <tbody class="question-type-fields fill-blank-fields">
                    <tr>
                        <th scope="row"><?php esc_html_e('Correct Answers', 'lwa-exams'); ?></th>
                        <td>
                            <div id="fib-answers-container">
                                <?php
                                $fib_answers = isset($question) && $question->type === 'Fill in the Blank' ?
                                    explode(',', $question->correct_answer) :
                                    [''];

                                foreach ($fib_answers as $index => $answer): ?>
                                    <div class="fib-answer-field" style="margin-bottom: 10px;">
                                        <input type="text"
                                            name="correct_answer_fib[]"
                                            value="<?php echo esc_attr($answer); ?>"
                                            placeholder="Correct answer #<?php echo $index + 1; ?>">
                                        <button type="button" class="button fib-remove-answer" <?php echo $index === 0 ? 'style="display:none;"' : ''; ?>>Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="fib-add-answer" class="button">Add Another Answer</button>
                            <p class="description"><?php esc_html_e('Enter all possible correct answers in order', 'lwa-exams'); ?></p>
                        </td>
                    </tr>
                </tbody>



                <!-- Explanation -->
                <tr class="explanation-field">
                    <th scope="row"><label for="explanation"><?php esc_html_e('Explanation', 'lwa-exams'); ?></label></th>
                    <td>
                        <textarea name="explanation" id="explanation" rows="2" class="large-text"><?php
                                                                                                    echo isset($question) ? esc_textarea($question->explanation) : '';
                                                                                                    ?></textarea>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php echo $action === 'add' ? esc_html__('Add Question', 'lwa-exams') : esc_html__('Update Question', 'lwa-exams'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=lwa-exams-questions&exam_id=' . $exam_id); ?>" class="button">
                    <?php esc_html_e('Cancel', 'lwa-exams'); ?>
                </a>
            </p>
        </form>

        <script>
            jQuery(document).ready(function($) {
                // Add new answer field
                $('#fib-add-answer').on('click', function() {
                    const container = $('#fib-answers-container');
                    const index = container.children().length;
                    const newField = $(`
            <div class="fib-answer-field" style="margin-bottom: 10px;">
                <input type="text" 
                       name="correct_answer_fib[]" 
                       placeholder="Correct answer #${index + 1}">
                <button type="button" class="button fib-remove-answer">Remove</button>
            </div>
        `);
                    container.append(newField);
                });

                // Remove answer field
                $(document).on('click', '.fib-remove-answer', function() {
                    $(this).parent().remove();
                    // Update numbering
                    $('#fib-answers-container .fib-answer-field').each(function(index) {
                        $(this).find('input').attr('placeholder', `Correct answer #${index + 1}`);
                    });
                });
            });
        </script>

        <script>
            jQuery(document).ready(function($) {
                // Toggle question type fields
                function toggleQuestionFields() {
                    var type = $('#type').val();

                    // Hide all question type fields first
                    $('.question-type-fields').hide().find('input, select, textarea').prop('disabled', true); // disable inputs in hidden blocks

                    // Show and enable the relevant field group
                    if (type === 'Multiple Select') {
                        $('.multiple-select-fields').show().find('input, select, textarea').prop('disabled', false);
                    } else if (type === 'True/False') {
                        $('.true-false-fields').show().find('input, select, textarea').prop('disabled', false);
                    } else if (type === 'Fill in the Blank') {
                        $('.fill-blank-fields').show().find('input, select, textarea').prop('disabled', false);
                    } else if (type === 'Multiple Choice') {
                        $('.multiple-choice-fields').show().find('input, select, textarea').prop('disabled', false);
                    }
                }

                // Initialize and watch for changes
                toggleQuestionFields();
                $('#type').change(toggleQuestionFields);

                // Form validation
                $('form.lwa-exams-form').submit(function(e) {
                    var type = $('#type').val();
                    var valid = true;
                    var errorMessage = '';

                    // Scope validation to only visible fields
                    var $visibleFields = $('.question-type-fields:visible');

                    // Check for at least two options filled for Multiple Choice and Multiple Select
                    if (type === 'Multiple Choice' || type === 'Multiple Select') {
                        var filledOptions = 0;

                        // Text inputs
                        $visibleFields.find('input[name^="option_"]:text').each(function() {
                            if ($(this).val().trim() !== '') {
                                filledOptions++;
                            }
                        });

                        // File inputs
                        $visibleFields.find('input[type="file"][name^="option_"]').each(function() {
                            if (this.files.length > 0) {
                                filledOptions++;
                            }
                        });

                        // Existing image previews
                        $visibleFields.find('.image-preview[data-option-filled="1"]').each(function() {
                            filledOptions++;
                        });

                        if (filledOptions < 2) {
                            errorMessage = '<?php esc_attr_e('Please provide at least two options (text or image)', 'lwa-exams'); ?>';
                            valid = false;
                        }
                    }

                    // Check correct answer based on type
                    if (type === 'Multiple Choice') {
                        if ($('#correct_answer_mc').val() === '') {
                            errorMessage = '<?php esc_attr_e('Please select the correct answer', 'lwa-exams'); ?>';
                            valid = false;
                        }
                    } else if (type === 'Multiple Select') {
                        if ($('input[name="correct_answers[]"]:checked').length === 0) {
                            errorMessage = '<?php esc_attr_e('Please select at least one correct answer', 'lwa-exams'); ?>';
                            valid = false;
                        }
                    } else if (type === 'True/False') {
                        if ($('input[name="correct_answer_tf"]:checked').length === 0) {
                            errorMessage = '<?php esc_attr_e('Please select the correct answer', 'lwa-exams'); ?>';
                            valid = false;
                        }
                    } else if (type === 'Fill in the Blank') {
                        if ($('input[name="correct_answer_fib"]').val().trim() === '') {
                            errorMessage = '<?php esc_attr_e('Please enter the correct answer', 'lwa-exams'); ?>';
                            valid = false;
                        }
                    }

                    if (!valid) {
                        alert(errorMessage);
                        e.preventDefault();
                        return false;
                    }

                    return true;
                });
            });
        </script>

    <?php endif; ?>
</div>