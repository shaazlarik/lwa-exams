<?php
/**
 * Plugin Name: LWA Exams
 * Plugin URI: https://github.com/shaazlarik/lwa-exams
 * Description: Interactive quizzes and results tracking system with detailed reporting and analytics for learners.
 * Version: 1.2.3
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: shaazlarik
 * Author URI: https://github.com/shaazlarik
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lwa-exams
 * Update URI: https://github.com/shaazlarik/lwa-exams
 */

if (!defined('ABSPATH')) {
    exit();
}

// Define constants
define('LWA_EXAMS_PATH', plugin_dir_path(__FILE__));
define('LWA_EXAMS_URL', plugin_dir_url(__FILE__));
define('LWA_EXAMS_VERSION', '1.2.3');
define('LWA_EXAMS_DB_VERSION', '1.0.2');

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// STEP 1: GitHub Plugin Update Checker
add_action('plugins_loaded', function () {
    require_once LWA_EXAMS_PATH . 'plugin-update-checker/plugin-update-checker.php';

    $updateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/shaazlarik/lwa-exams',
        __FILE__,
        'lwa-exams'
    );

    $updateChecker->getVcsApi()->enableReleaseAssets();

    if (method_exists($updateChecker, 'setChangelogFilename')) {
        $updateChecker->setChangelogFilename('readme.txt');
    }
}, 1);

// STEP 2: Detect Plugin Update via WordPress
add_action('upgrader_process_complete', function ($upgrader, $options) {
    if (
        $options['action'] === 'update' &&
        $options['type'] === 'plugin' &&
        isset($options['plugins']) &&
        in_array(plugin_basename(__FILE__), $options['plugins'], true)
    ) {
        update_option('lwa_db_needs_update', true); // Flag to check DB on next load
    }
}, 10, 2);

// STEP 3: Run DB update if flagged (and version is lower)
add_action('admin_init', function () {
    if (!is_admin() || !get_option('lwa_db_needs_update')) {
        return; // Exit early if not in admin area or no update is needed
    }
    $current_db_version = get_option('lwa_exams_db_version', '1.0.0');

    if (version_compare($current_db_version, LWA_EXAMS_DB_VERSION, '<')) {
        // error_log('[' . date('Y-m-d H:i:s') . '] [LWA_EXAMS] Compare done');

        require_once LWA_EXAMS_PATH . 'admin/class-db-updates.php';

        error_log('[' . date('Y-m-d H:i:s') . '] [LWA_EXAMS] class file included');


        if (class_exists('LWA_EXAMS_DB_Updates')) {
            // error_log('[' . date('Y-m-d H:i:s') . '] [LWA_EXAMS] DB update needed. Running updates.');
            LWA_EXAMS_DB_Updates::run_updates();
            // error_log('[' . date('Y-m-d H:i:s') . '] [LWA_EXAMS] DB update complete.');
        }
    } else {
        error_log('[' . date('Y-m-d H:i:s') . '] [LWA_EXAMS] DB already up to date. Skipping update.');
    }    
});


// 🔧 Core Admin Class (required for activation, deactivation, uninstall)
require_once LWA_EXAMS_PATH . 'admin/class-dashboard.php';

register_activation_hook(__FILE__, ['LWA_EXAMS_Dashboard', 'activate']);
register_deactivation_hook(__FILE__, ['LWA_EXAMS_Dashboard', 'deactivate']);
register_uninstall_hook(__FILE__, ['LWA_EXAMS_Dashboard', 'uninstall']);

if (is_admin()) {
    function lwa_exams_admin_init()
    {
        $classes = [
            'dashboard',
            'exams',
            'categories',
            'questions',
        ];

        foreach ($classes as $class) {
            require_once LWA_EXAMS_PATH . "admin/class-{$class}.php";
            $classname = "LWA_EXAMS_{$class}";
            new $classname();
        }
    }
    add_action('plugins_loaded', 'lwa_exams_admin_init');
}

// Initialize frontend
require_once LWA_EXAMS_PATH . 'frontend/class-frontend.php';
