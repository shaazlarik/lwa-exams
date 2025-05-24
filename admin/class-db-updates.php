<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LWA_EXAMS_DB_Updates
{

    /**
     * Run all required database updates
     */
    public static function run_updates()
    {
        $current_db_version = get_option('lwa_exams_db_version', '1.0.0');

        if (version_compare($current_db_version, LWA_EXAMS_DB_VERSION, '<')) {
            // Add admin notice hook
            add_action('admin_notices', [__CLASS__, 'show_update_notice']);

            try {             

                // If all updates succeeded, update version
                update_option('lwa_exams_db_version', LWA_EXAMS_DB_VERSION);

                // Clear the update flag only after successful updates
                delete_option('lwa_db_needs_update');

                // Set success transient
                set_transient('lwa_exams_db_update_success', true, 30);
            } catch (Exception $e) {
                // Log the error
                error_log('[' . date('Y-m-d H:i:s') . '] LWA Exams DB Update Failed: ' . $e->getMessage());

                // Set error transient
                set_transient('lwa_exams_db_update_error', $e->getMessage(), 30);
            }
        }
    }


    /**
     * Show admin notices for update status
     */
    public static function show_update_notice()
    {
        if ($error = get_transient('lwa_exams_db_update_error')) {
?>
            <div class="notice notice-error">
                <p><strong>LWA Exams Database Update Failed:</strong> <?php echo esc_html($error); ?></p>
                <p>Please contact support or check your error logs.</p>
            </div>
        <?php
            delete_transient('lwa_exams_db_update_error');
        }

        if (get_transient('lwa_exams_db_update_success')) {
        ?>
            <div class="notice notice-success">
                <p><strong>LWA Exams:</strong> Database updated successfully to version <?php echo esc_html(LWA_EXAMS_DB_VERSION); ?></p>
            </div>
<?php
            delete_transient('lwa_exams_db_update_success');
        }
    }
}
