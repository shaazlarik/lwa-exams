<?php if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get stats
$stats = [
    'total_users' => count_users()['total_users'],
    'active_exams' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}exams WHERE is_active = 1"),
    'total_attempts' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}attempts"),
    'pass_rate' => $wpdb->get_var("SELECT ROUND(SUM(passed) / COUNT(*) * 100) FROM {$wpdb->prefix}attempts")
];

?>

<div class="wrap">
    <!-- Top Row: Page Title -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h1 class="wp-heading-inline">LWA Exams Dashboard</h1>
    </div>

    <!-- Cards Row -->
    <div class="lwa-exams-stats">
        <div class="stat-card">
            <h3><?php echo esc_html__('Total Users', 'lwa-exams'); ?></h3>
            <p><?php echo esc_html($stats['total_users'] ?: 0); ?></p>
        </div>
        <div class="stat-card">
            <h3><?php echo esc_html__('Active Exams', 'lwa-exams'); ?></h3>
            <p><?php echo esc_html($stats['active_exams'] ?: 0); ?></p>
        </div>
        <div class="stat-card">
            <h3><?php echo esc_html__('Total Attempts', 'lwa-exams'); ?></h3>
            <p><?php echo esc_html($stats['total_attempts'] ?: 0); ?></p>
        </div>
        <div class="stat-card">
            <h3><?php echo esc_html__('Pass Rate', 'lwa-exams'); ?></h3>
            <p><?php echo esc_html($stats['pass_rate']); ?>%</p>
        </div>
    </div>

    <!-- Top Row: Page Title -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h1 class="wp-heading-inline">Recent Activity</h1>

    </div>

    <!-- Main Form (contains bulk actions and table) -->
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="bulk-action-form">
        <input type="hidden" name="page" value="lwa-exams-attempts">
        <input type="hidden" name="action" value="lwa_exams_bulk_delete_attempts">
        <?php wp_nonce_field('bulk-attempts'); ?>

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
                <label for="filter-by-user" class="screen-reader-text"><?php esc_html_e('Filter by user', 'lwa-exams'); ?></label>
                <select name="attempt_filter" id="filter-by-user">
                    <option value=""><?php esc_html_e('All attempts', 'lwa-exams'); ?></option>
                    <?php
                    // Get unique user IDs from attempts
                    $users_attempted = array_unique(wp_list_pluck($attempts, 'user_id'));

                    // Get user data for each unique user ID
                    foreach ($users_attempted as $user_id) :
                        $user = get_user_by('id', $user_id);
                        if ($user) : ?>
                            <option value="<?php echo esc_attr($user_id); ?>" <?php selected(isset($_GET['attempt_filter']) ? $_GET['attempt_filter'] : '', $user_id); ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                    <?php endif;
                    endforeach; ?>
                </select>
                <button type="button" id="filter-submit" class="button">
                    <?php echo !empty($_GET['attempt_filter']) ? esc_html__('Clear Filter', 'lwa-exams') : esc_html__('Filter', 'lwa-exams'); ?>
                </button>
            </div>

            <!-- Search (GET form needs to be separate but we'll handle it with JavaScript) -->
            <div class="alignright actions">
                <div class="search-form">

                    <label class="screen-reader-text" for="attempt-search-input"><?php esc_html_e('Search attempts', 'lwa-exams'); ?></label>
                    <input type="search" id="attempt-search-input" name="s" value="<?php echo esc_attr(isset($_GET['s']) ? $_GET['s'] : ''); ?>">
                    <button type="button" id="search-submit" class="button">
                        <?php echo !empty($_GET['s']) ? esc_html__('Clear Search', 'lwa-exams') : esc_html__('Search attempts', 'lwa-exams'); ?>
                    </button>

                </div>
            </div>
            <br class="clear">
        </div>

        <!-- Table -->
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <input type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-primary">User</th>
                    <th scope="col" class="manage-column">Exam</th>
                    <th scope="col" class="manage-column">Date</th>
                    <th scope="col" class="manage-column">Score</th>
                    <th scope="col" class="manage-column">Result</th>
                </tr>
            </thead>

            <tbody id="the-list">
                <?php foreach ($attempts as $attempt) : ?>
                    <tr class="iedit">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="attempt_ids[]" value="<?php echo esc_attr($attempt->id); ?>">
                        </th>
                        <td class="column-primary" data-colname="<?php esc_attr_e('User', 'lwa-exams'); ?>">
                            <strong><?php echo esc_html($attempt->display_name); ?></strong>
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=lwa-exams&action=results&attempt_id=' . $attempt->id); ?>"><?php esc_html_e('View', 'lwa-exams'); ?></a> |
                                </span>
                                <span class="delete">
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=lwa_exams_delete_attempt&id=' . $attempt->id), 'lwa_exams_delete_attempt'); ?>"
                                        onclick="return confirm('<?php esc_attr_e('Are you sure?', 'lwa-exams'); ?>')"><?php esc_html_e('Delete', 'lwa-exams'); ?></a>
                                </span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'lwa-exams'); ?></span></button>
                        </td>
                        <td data-colname="Exam"><?php echo esc_html($attempt->title); ?></td>
                        <td data-colname="Date">
                            <?php
                            if ($attempt->end_time) {
                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($attempt->end_time)));
                            } else {
                                echo esc_html__('Not completed', 'lwa-exams');
                            }
                            ?>
                        </td>
                        <td data-colname="Score"><?php echo esc_html($attempt->score); ?>%</td>
                        <td data-colname="Result">
                            <?php if ($attempt->passed) : ?>
                                <span class="dashicons dashicons-yes" style="color: green;"></span> Passed
                            <?php else : ?>
                                <span class="dashicons dashicons-no" style="color: red;"></span> Failed
                            <?php endif; ?>
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
                printf(
                    _n('%s attempt', '%s attempts', $total_items, 'lwa-exams'),
                    number_format_i18n($total_items)
                );
                ?>
            </span>
        </div>
        <div class="tablenav-pages">
            <?php
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $total_pages = ceil($total_items / $per_page);

            $base_url = add_query_arg([
                'page' => 'lwa-exams',
                'attempt_filter' => isset($_GET['attempt_filter']) ? $_GET['attempt_filter'] : '',
                's' => isset($_GET['s']) ? $_GET['s'] : ''
            ], admin_url('admin.php'));

            echo paginate_links([
                'base' => add_query_arg('paged', '%#%', $base_url),
                'format' => '',
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'total' => $total_pages,
                'current' => $current_page,
                'add_args' => false, // This is important to prevent duplicate parameters
            ]);
            ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Handle filter submit/clear
            $('#filter-submit').on('click', function() {
                var url = '<?php echo admin_url('admin.php?page=lwa-exams'); ?>';
                var currentPage = '<?php echo isset($_GET['paged']) ? intval($_GET['paged']) : 1; ?>';
                var filterValue = $('#filter-by-user').val();

                // If button says "Clear Filter" or filter value is empty, clear it
                if ($(this).text().trim() === 'Clear Filter' || !filterValue) {
                    window.location.href = removeParam('attempt_filter', url);
                }
                // Otherwise apply the filter and reset to page 1
                else {
                    window.location.href = url + '&attempt_filter=' + filterValue + '&paged=1';
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
                var url = '<?php echo admin_url('admin.php?page=lwa-exams'); ?>';
                var searchValue = $('#attempt-search-input').val().trim();

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
            $('#attempt-search-input').on('keypress', function(e) {
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
                        alert('<?php esc_attr_e('Please select at least one attempt', 'lwa-exams'); ?>');
                        return false;
                    }
                    return confirm('<?php esc_attr_e('Are you sure you want to delete these attempts?', 'lwa-exams'); ?>');
                }
            });

            // Select all checkboxes
            $('#cb-select-all-1').click(function() {
                $('tbody input[type="checkbox"]').prop('checked', $(this).prop('checked'));
            });
        });
    </script>

</div>