<?php
if (!defined('ABSPATH')) {
    exit;
}

class LWA_EXAMS_DB_Management
{
    private $changelog = [];

    public function __construct()
    {
        // Parse the changelog during initialization
        $this->parse_changelog();
    }

    /**
     * Render the admin database management page
     */
    public function render_admin_db_management_page()
    {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $message = ''; // Initialize message variable

        // Handle manual update request
        if (isset($_POST['lwa_manual_db_update']) && check_admin_referer('lwa_manual_db_update_nonce')) {
            try {
                require_once LWA_EXAMS_PATH . 'admin/class-db-updates.php';
                LWA_EXAMS_DB_Updates::run_updates();
                $message = '<div class="notice notice-success"><p>' . __('Database updated successfully.', 'lwa-exams') . '</p></div>';
            } catch (Exception $e) {
                $message = '<div class="notice notice-error"><p>' . __('Database update failed:', 'lwa-exams') . ' ' . esc_html($e->getMessage()) . '</p></div>';
            }
        }

        // Get current and required DB versions
        $current_plugin_version = LWA_EXAMS_VERSION;
        $current_db_version = get_option('lwa_exams_db_version', '1.0.0');
        $required_db_version = LWA_EXAMS_DB_VERSION;

        // Determine if an update is needed
        $update_needed = version_compare($current_db_version, $required_db_version, '<');

        // Paginate the changelog
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 10; // Number of changelog entries per page
        $paginated_changelog = $this->get_paginated_changelog($paged, $per_page);
        $total_items = count($this->changelog);

        // Prepare data for the view
        $view_data = [
            'message' => $message,
            'current_plugin_version' => $current_plugin_version,
            'current_db_version' => $current_db_version,
            'required_db_version' => $required_db_version,
            'update_needed' => $update_needed,
            'changelog' => $paginated_changelog,
            'total_items' => $total_items,
            'per_page' => $per_page,
            'paged' => $paged,
        ];

        // Include the view file
        include LWA_EXAMS_PATH . 'admin/views/db-management.php';
    }

    /**
     * Parse the changelog from readme.txt
     */
    private function parse_changelog()
    {
        $readme_path = LWA_EXAMS_PATH . 'readme.txt';
        if (!file_exists($readme_path)) {
            error_log('[LWA Exams] readme.txt file not found.');
            return;
        }

        $content = file_get_contents($readme_path);
        $lines = explode("\n", $content);

        // Debug: Log the first few lines of the file
        error_log('[LWA Exams] First few lines of readme.txt: ' . print_r(array_slice($lines, 0, 5), true));

        // Find the start of the changelog section
        $changelog_start = false;
        $current_version = null;
        $current_date = null; // Initialize $current_date
        $changes = [];
        foreach ($lines as $line) {
            // Ignore empty lines at the start of the changelog
            if (trim($line) === '' && !$changelog_start) {
                continue;
            }

            if (strpos($line, '== Changelog ==') !== false) {
                $changelog_start = true;
                continue;
            }

            // Stop when we reach a new section header (e.g., == Screenshots ==)
            if ($changelog_start && strpos($line, '== ') === 0) {
                break; // Stop when we reach a new section
            }

            if ($changelog_start) {
                if (preg_match('/^= (\d+\.\d+\.\d+) - (\d{4}-\d{2}-\d{2}) =/', $line, $matches)) {
                    if ($current_version !== null) {
                        // Ensure $current_date is defined before using it
                        if ($current_date === null) {
                            error_log('[LWA Exams] Missing date for version: ' . $current_version);
                            $current_date = 'Unknown'; // Fallback value
                        }
                        $this->changelog[] = [
                            'version' => $current_version,
                            'date' => $current_date,
                            'changes' => $changes,
                        ];
                    }
                    $current_version = $matches[1];
                    $current_date = $matches[2]; // Initialize $current_date
                    $changes = [];
                } else {
                    $changes[] = $line;
                }
            }
        }

        // Add the last version (if any)
        if ($current_version !== null) {
            // Ensure $current_date is defined before using it
            if ($current_date === null) {
                error_log('[LWA Exams] Missing date for version: ' . $current_version);
                $current_date = 'Unknown'; // Fallback value
            }
            $this->changelog[] = [
                'version' => $current_version,
                'date' => $current_date,
                'changes' => $changes,
            ];
        }

        // Debug: Log the parsed changelog
        error_log('[LWA Exams] Parsed changelog: ' . print_r($this->changelog, true));
    }

    /**
     * Get paginated changelog entries
     */
    private function get_paginated_changelog($paged, $per_page)
    {
        $offset = ($paged - 1) * $per_page;
        return array_slice($this->changelog, $offset, $per_page);
    }
}
