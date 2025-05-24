<?php if (!defined('ABSPATH')) {
    exit();
}

global $wpdb;

// Ensure we have access to the controller's table properties
if (!isset($this) || !property_exists($this, 'categories_table')) {
    wp_die('Controller instance not available');
}

?>

<div class="wrap">
    <?php if ($action == 'list') : ?>
        <h1 class="wp-heading-inline">
            <?php echo esc_html__('Categories', 'lwa-exams'); ?>
            <a href="<?php echo admin_url('admin.php?page=lwa-exams-categories&action_categories=add'); ?>" class="page-title-action">
                <?php echo esc_html__('Add New', 'lwa-exams'); ?>
            </a>
        </h1>

        <!-- Main Form (contains bulk actions and table) -->
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="bulk-action-form">
            <input type="hidden" name="page" value="lwa-exams-categories">
            <input type="hidden" name="action" value="lwa_exams_bulk_delete_categories">
            <?php wp_nonce_field('bulk-categories'); ?>

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
                    <label for="filter-by-category" class="screen-reader-text"><?php esc_html_e('Filter by category', 'lwa-exams'); ?></label>
                    <select name="category_filter" id="filter-by-category">
                        <option value=""><?php esc_html_e('All categories', 'lwa-exams'); ?></option>
                        <?php foreach ($categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat->id); ?>" <?php selected(isset($_GET['category_filter']) ? $_GET['category_filter'] : '', $cat->id); ?>>
                                <?php echo esc_html($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="filter-submit" class="button">
                        <?php echo !empty($_GET['category_filter']) ? esc_html__('Clear Filter', 'lwa-exams') : esc_html__('Filter', 'lwa-exams'); ?>
                    </button>
                </div>

                <!-- Search (GET form needs to be separate but we'll handle it with JavaScript) -->
                <div class="alignright actions">
                    <div class="search-form">
                        <label class="screen-reader-text" for="category-search-input"><?php esc_html_e('Search categories', 'lwa-exams'); ?></label>
                        <input type="search" id="category-search-input" name="s" value="<?php echo esc_attr(isset($_GET['s']) ? $_GET['s'] : ''); ?>">
                        <button type="button" id="search-submit" class="button">
                            <?php echo !empty($_GET['s']) ? esc_html__('Clear Search', 'lwa-exams') : esc_html__('Search categories', 'lwa-exams'); ?>
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
                        <th class="manage-column column-primary"><?php esc_html_e('Name', 'lwa-exams'); ?></th>
                        <th class="manage-column"><?php esc_html_e('Description', 'lwa-exams'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category) : ?>
                        <tr class="iedit">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="category_ids[]" value="<?php echo esc_attr($category->id); ?>">
                            </th>
                            <td class="column-primary" data-colname="<?php esc_attr_e('Name', 'lwa-exams'); ?>">
                                <strong><?php echo esc_html($category->name); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo admin_url('admin.php?page=lwa-exams-categories&action_categories=edit&id=' . $category->id); ?>"><?php esc_html_e('Edit', 'lwa-exams'); ?></a> |
                                    </span>
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=lwa_exams_delete_category&id=' . $category->id), 'lwa_exams_delete_category'); ?>"
                                            onclick="return confirm('<?php esc_attr_e('Are you sure?', 'lwa-exams'); ?>')"><?php esc_html_e('Delete', 'lwa-exams'); ?></a>
                                    </span>
                                </div>
                                <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'lwa-exams'); ?></span></button>
                            </td>
                            <td data-colname="<?php esc_attr_e('Description', 'lwa-exams'); ?>"><?php echo esc_html($category->description); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        <div class="tablenav bottom">
            <div class="alignleft">
                <span class="displaying-num">
                    <?php
                    // Get the total count from the $categories array
                    $total_categories = is_array($categories) ? count($categories) : 0;

                    printf(
                        _n('%s category', '%s categories', $total_categories, 'lwa-exams'),
                        number_format_i18n($total_categories)
                    );
                    ?>
                </span>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                // Handle filter submit/clear
                $('#filter-submit').on('click', function() {
                    var url = '<?php echo admin_url('admin.php?page=lwa-exams-categories'); ?>';

                    // If button says "Clear Filter" (meaning filter is active), clear it
                    if ($(this).text().trim() === 'Clear Filter') {
                        window.location.href = url;
                    }
                    // Otherwise apply the filter
                    else {
                        var filterValue = $('#filter-by-category').val();
                        window.location.href = url + '&category_filter=' + filterValue;
                    }
                });

                // Handle search submit/clear
                $('#search-submit').on('click', function() {
                    var url = '<?php echo admin_url('admin.php?page=lwa-exams-categories'); ?>';

                    // If button says "Clear Search" (meaning search is active), clear it
                    if ($(this).text().trim() === 'Clear Search') {
                        window.location.href = url;
                    }
                    // Otherwise apply the search
                    else {
                        var searchValue = $('#category-search-input').val();
                        window.location.href = url + '&s=' + encodeURIComponent(searchValue);
                    }
                });


                // Handle Enter key in search field
                $('#category-search-input').on('keypress', function(e) {
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
                            alert('<?php esc_attr_e('Please select at least one category', 'lwa-exams'); ?>');
                            return false;
                        }
                        return confirm('<?php esc_attr_e('Are you sure you want to delete these categories?', 'lwa-exams'); ?>');
                    }
                });

                // Select all checkboxes
                $('#cb-select-all-1').click(function() {
                    $('tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
                });
            });
        </script>

    <?php else : ?>
        <h2><?php echo $action === 'add' ? esc_html__('Add New Category', 'lwa-exams') : esc_html__('Edit Category', 'lwa-exams'); ?></h2>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="lwa_exams_save_categories">
            <?php wp_nonce_field('lwa_exams_categories', 'lwa_exams_categories_nonce'); ?>
            <input type="hidden" name="action_categories" value="<?php echo $action === 'add' ? 'create_category' : 'update_category'; ?>">

            <?php if ($action === 'edit' && isset($category)) : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($category->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="name"><?php echo esc_html__('Name', 'lwa-exams'); ?></label></th>
                    <td>
                        <input type="text" name="name" id="name" class="regular-text"
                            value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : (isset($category) ? esc_attr($category->name) : ''); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="description"><?php echo esc_html__('Description', 'lwa-exams'); ?></label></th>
                    <td>
                        <textarea name="description" id="description" rows="5" class="large-text" required><?php
                                                                                                            echo isset($_POST['description']) ? esc_textarea($_POST['description']) : (isset($category) ? esc_textarea($category->description) : '');
                                                                                                            ?></textarea>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php echo $action === 'add' ? esc_html__('Add Category', 'lwa-exams') : esc_html__('Update Category', 'lwa-exams'); ?>
                </button>
                <a href="<?php echo admin_url('admin.php?page=lwa-exams-categories'); ?>" class="button">
                    <?php echo esc_html__('Cancel', 'lwa-exams'); ?>
                </a>
            </p>
        </form>
    <?php endif; ?>

</div>