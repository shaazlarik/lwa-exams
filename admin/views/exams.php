<?php if (!defined('ABSPATH')) {
    exit();
}


global $wpdb;


// Ensure we have access to the controller's table properties
if (!isset($this) || !property_exists($this, 'exams_table')) {
    wp_die('Controller instance not available');
}
// Debug output
// echo '<pre>';
// print_r($_POST); 
// echo 'Action: ' . $action . "\n";
// echo 'Exams count: ' . count($exams) . "\n";
// echo 'Categories count: ' . count($all_categories) . "\n";
// echo '</pre>';
?>

<div class="wrap">
    <!-- Top Row: Page Title -->
    <?php if ($action == 'list') : ?>
        <h1 class="wp-heading-inline">
            <?php echo esc_html__('Exams', 'lwa-exams'); ?>
            <a href="<?php echo admin_url('admin.php?page=lwa-exams-exams&action_exams=add'); ?>" class="page-title-action">
                <?php echo esc_html__('Add New', 'lwa-exams'); ?>
            </a>
        </h1>

        <!-- Main Form (contains bulk actions and table) -->
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="bulk-action-form">
            <input type="hidden" name="page" value="lwa-exams-exams">
            <input type="hidden" name="action" value="lwa_exams_bulk_delete_exams">
            <?php wp_nonce_field('bulk-exams'); ?>


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
                    <label for="filter-by-exam" class="screen-reader-text"><?php esc_html_e('Filter by exam', 'lwa-exams'); ?></label>
                    <select name="category_filter" id="filter-by-exam">
                        <option value=""><?php esc_html_e('All Exams', 'lwa-exams'); ?></option>
                        <?php foreach ($exams as $exam) : ?>
                            <option value="<?php echo esc_attr($exam->id); ?>" <?php selected(isset($_GET['exam_filter']) ? $_GET['exam_filter'] : 0, $exam->id); ?>>
                                <?php echo esc_html($exam->title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="filter-submit" class="button">
                        <?php echo !empty($_GET['exam_filter']) ? esc_html__('Clear Filter', 'lwa-exams') : esc_html__('Filter', 'lwa-exams'); ?>
                    </button>
                </div>

                <!-- Search (GET form needs to be separate but we'll handle it with JavaScript) -->
                <div class="alignright actions">
                    <div class="search-form">
                        <label class="screen-reader-text" for="exam-search-input"><?php esc_html_e('Search exams', 'lwa-exams'); ?></label>
                        <input type="search" id="exam-search-input" name="s" value="<?php echo esc_attr(isset($_GET['s']) ? $_GET['s'] : ''); ?>">
                        <button type="button" id="search-submit" class="button">
                            <?php echo !empty($_GET['s']) ? esc_html__('Clear Search', 'lwa-exams') : esc_html__('Search exams', 'lwa-exams'); ?>
                        </button>

                    </div>
                </div>
                <br class="clear">
            </div>
            <!-- Categories Table -->
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th scope="col" class="manage-column column-primary"><?php echo esc_html__('Title', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Categories', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Questions', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Time Limit', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Passing Score', 'lwa-exams'); ?></th>
                        <th scope="col" class="manage-column"><?php echo esc_html__('Status', 'lwa-exams'); ?></th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php foreach ($exams as $exam) :
                        $categories = $wpdb->get_results($wpdb->prepare(
                            "SELECT c.name FROM {$this->exam_categories_table} ec 
                             JOIN {$this->categories_table} c ON ec.category_id = c.id
                             WHERE ec.exam_id = %d",
                            $exam->id
                        ));
                    ?>
                        <tr class="iedit">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="exam_ids[]" value="<?php echo esc_attr($exam->id); ?>">
                            </th>
                            <td class="has-row-actions column-primary" data-colname="Title">
                                <strong><?php echo esc_html($exam->title); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=lwa-exams-exams&action_exams=edit&id=' . esc_attr($exam->id)); ?>" aria-label="Edit"><?php esc_html_e('Edit', 'lwa-exams'); ?>
                                        </a> | </span>
                                    <span class="questions">
                                        <a href="<?php echo admin_url('admin.php?page=lwa-exams-questions&exam_id=' . esc_attr($exam->id)); ?>" aria-label="Questions"><?php esc_html_e('Questions', 'lwa-exams'); ?>
                                        </a> | </span>
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=lwa_exams_delete_exam&id=' . $exam->id), 'lwa_exams_delete_exam'); ?>" class="submitdelete" aria-label="Delete"
                                            onclick="return confirm('Are you sure you want to delete this exam?')"><?php esc_html_e('Delete', 'lwa-exams'); ?>
                                        </a></span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'lwa-exams'); ?></span></button>
                            </td>
                            <td data-colname="Categories">
                                <?php foreach ($categories as $cat) : ?>
                                    <span class="category-badge"><?php echo esc_html($cat->name); ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td data-colname="Questions"><?php echo esc_html($exam->question_count); ?></td>
                            <td data-colname="Time Limit"><?php echo esc_html($exam->time_limit_minutes); ?> mins</td>
                            <td data-colname="Passing Score"><?php echo esc_html($exam->passing_score); ?>%</td>
                            <td data-colname="Status">
                                <span class="status-badge <?php echo $exam->is_active ? 'active' : 'inactive'; ?>">
                                    <?php echo $exam->is_active ? esc_html__('Active', 'lwa-exams') : esc_html__('Inactive', 'lwa-exams'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <div class="tablenav bottom">
            <div class="alignleft">
                <span class="displaying-num">
                    <?php
                    // Get total exam count from the $exams array
                    $total_exams = is_array($exams) ? count($exams) : 0;

                    printf(
                        _n('%s exam', '%s exams', $total_exams, 'lwa-exams'),
                        number_format_i18n($total_exams)
                    );
                    ?>
                </span>
            </div>
            <div class="tablenav-pages">
                <?php
                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $total_pages = ceil($total_items / $per_page);

                $base_url = add_query_arg([
                    'page' => 'lwa-exams-exams',
                    'exam_filter' => isset($_GET['exam_filter']) ? $_GET['exam_filter'] : '',
                    's' => isset($_GET['s']) ? $_GET['s'] : ''
                ], admin_url('admin.php'));

                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%', $base_url),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'add_args' => false,
                ]);
                ?>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                // Handle filter submit/clear
                $('#filter-submit').on('click', function() {
                    var url = '<?php echo admin_url('admin.php?page=lwa-exams-exams'); ?>';
                    var currentPage = '<?php echo isset($_GET['paged']) ? intval($_GET['paged']) : 1; ?>';
                    var filterValue = $('#filter-by-exam').val();

                    // If button says "Clear Filter" or filter value is empty, clear it
                    if ($(this).text().trim() === 'Clear Filter' || !filterValue) {
                        window.location.href = removeParam('exam_filter', url);
                    }
                    // Otherwise apply the filter and reset to page 1
                    else {
                        window.location.href = url + '&exam_filter=' + filterValue + '&paged=1';
                    }
                });

                // Helper function to remove a parameter from URL
                function removeParam(key, sourceURL) {
                    var rtn = sourceURL.split("?")[0],
                        param,
                        params_arr = [],
                        queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
                    if (queryString !== "") {
                        params_arr = queryString.split("&");
                        for (var i = params_arr.length - 1; i >= 0; i -= 1) {
                            param = params_arr[i].split("=")[0];
                            if (param === key) {
                                params_arr.splice(i, 1);
                            }
                        }
                        if (params_arr.length) {
                            rtn = rtn + "?" + params_arr.join("&");
                        }
                    }
                    return rtn;
                }

                // Handle search submit/clear
                $('#search-submit').on('click', function() {
                    var url = '<?php echo admin_url('admin.php?page=lwa-exams-exams'); ?>';
                    var searchValue = $('#exam-search-input').val().trim();

                    // If button says "Clear Search" or search is empty, clear it
                    if ($(this).text().trim() === 'Clear Search' || !searchValue) {
                        window.location.href = removeParam('s', url);
                    }
                    // Otherwise apply the search and reset to page 1
                    else {
                        window.location.href = url + '&s=' + encodeURIComponent(searchValue) + '&paged=1';
                    }
                });

                // Handle Enter key in search field
                $('#exam-search-input').on('keypress', function(e) {
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
                            alert('<?php esc_attr_e('Please select at least one exam', 'lwa-exams'); ?>');
                            return false;
                        }
                        return confirm('<?php esc_attr_e('Are you sure you want to delete these exams?', 'lwa-exams'); ?>');
                    }
                });

                // Select all checkboxes
                $('#cb-select-all-1').click(function() {
                    $('tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
                });
            });
        </script>
    <?php else : ?>
        <h2><?php echo $action === 'add' ? esc_html__('Add New Exam', 'lwa-exams') : esc_html__('Edit Exam', 'lwa-exams'); ?></h2>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="lwa_exams_save_exam">
            <?php wp_nonce_field('lwa_exams_exams', 'lwa_exams_exams_nonce'); ?>
            <input type="hidden" name="action_exams" value="<?php echo $action === 'add' ? 'create_exam' : 'update_exam'; ?>">



            <?php if ($action === 'edit' && isset($exam)) : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($exam->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="title"><?php echo esc_html__('Title', 'lwa-exams'); ?></label></th>
                    <td>
                        <input type="text" name="title" id="title" class="regular-text"
                            value="<?php echo isset($exam) ? esc_attr($exam->title) : ''; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="description"><?php echo esc_html__('Description', 'lwa-exams'); ?></label></th>
                    <td>
                        <textarea name="description" id="description" rows="5" class="large-text"><?php
                                                                                                    echo isset($exam) ? esc_textarea($exam->description) : '';
                                                                                                    ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="time_limit"><?php echo esc_html__('Time Limit (minutes)', 'lwa-exams'); ?></label></th>
                    <td>
                        <input type="number" name="time_limit" id="time_limit" min="1"
                            value="<?php echo isset($exam) ? esc_attr($exam->time_limit_minutes) : '30'; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="passing_score"><?php echo esc_html__('Passing Score (%)', 'lwa-exams'); ?></label></th>
                    <td>
                        <input type="number" name="passing_score" id="passing_score" min="1" max="100"
                            value="<?php echo isset($exam) ? esc_attr($exam->passing_score) : '70'; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo esc_html__('Status', 'lwa-exams'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?php
                                                                                echo (isset($exam) && $exam->is_active) ? 'checked' : ''; ?>>
                            <?php echo esc_html__('Active (visible to users)', 'lwa-exams'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php echo esc_html__('Categories', 'lwa-exams'); ?></label></th>
                    <td>
                        <div class="category-checkboxes">
                            <?php foreach ($all_categories as $category) : ?>
                                <label class="category-checkbox">
                                    <input type="checkbox" name="categories[]" value="<?php echo esc_attr($category->id); ?>" <?php
                                                                                                                                echo (isset($exam) && in_array($category->id, $exam->categories)) ? 'checked' : ''; ?>>
                                    <?php echo esc_html($category->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php echo $action === 'add' ? esc_html__('Add Exam', 'lwa-exams') : esc_html__('Update Exam', 'lwa-exams'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=lwa-exams-exams'); ?>" class="button">
                    <?php echo esc_html__('Cancel', 'lwa-exams'); ?>
                </a>
            </p>
        </form>
    <?php endif; ?>
</div>