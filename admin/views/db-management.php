<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">
    <h1>LWA Exams Database Management</h1>

    <?php if (!empty($view_data['message'])) echo $view_data['message']; ?>

    <!-- <table class="widefat fixed" style="margin-top: 20px;"> -->
    <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Database Information</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="exclude"><strong>Current Plugin Version:</strong></td>
                <td class="exclude"><?php echo esc_html($view_data['current_plugin_version']); ?></td>
            </tr>
             <tr>
                <td class="exclude"><strong>Current Database Version:</strong></td>
                <td class="exclude"><?php echo esc_html($view_data['current_db_version']); ?></td>
            </tr>
            <tr>
                <td class="exclude"><strong>Required Database Version:</strong></td>
                <td class="exclude"><?php echo esc_html($view_data['required_db_version']); ?></td>
            </tr>
            <tr>
                <td class="exclude"><strong>Status:</strong></td>
                <td class="exclude">
                    <?php
                    if ($view_data['update_needed']) {
                        echo '<span style="color: red;">Update Required</span>';
                    } else {
                        echo '<span style="color: green;">Up to Date</span>';
                    }
                    ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php if ($view_data['update_needed']): ?>
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('lwa_manual_db_update_nonce'); ?>
            <input type="hidden" name="lwa_manual_db_update" value="1">
            <button type="submit" class="button button-primary">Update Database Manually</button>
        </form>
    <?php endif; ?>

    <!-- Changelog Table -->
    <h2>Changelog</h2>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col" style="width: 150px;" class="manage-column column-primary"><?php esc_html_e('Version', 'lwa-exams'); ?></th>
                <th scope="col" style="width: 200px;" class="manage-column"><?php esc_html_e('Date', 'lwa-exams'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Changes', 'lwa-exams'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php foreach ($view_data['changelog'] as $entry): ?>
                <tr class="iedit">
                    <td class="column-primary" data-colname="<?php esc_attr_e('Version', 'lwa-exams'); ?>">
                        <strong><?php echo esc_html($entry['version']); ?></strong>                       
                        <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e('Show more details', 'lwa-exams'); ?></span></button>
                    </td>
                    <td data-colname="<?php esc_attr_e('Date', 'lwa-exams'); ?>">
                        <?php echo esc_html($entry['date']); ?>
                    </td>
                    <td data-colname="<?php esc_attr_e('Changes', 'lwa-exams'); ?>">
                        <?php echo implode('<br>', array_map('esc_html', $entry['changes'])); ?>
                    </td>
                </tr>

            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            $base_url = add_query_arg([
                'page' => 'lwa-exams-db-management',
            ], admin_url('admin.php'));

            echo paginate_links([
                'base' => add_query_arg('paged', '%#%', $base_url),
                'format' => '',
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'total' => ceil($view_data['total_items'] / $view_data['per_page']),
                'current' => $view_data['paged'],
                'add_args' => false,
            ]);
            ?>
        </div>
    </div>
</div>