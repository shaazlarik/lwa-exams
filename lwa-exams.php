<?php

/**
 * Plugin Name: LWA Exams
 * Plugin URI: https://github.com/shaazlarik/lwa-exams
 * Description: Interactive quizzes and results tracking system with detailed reporting and analytics for learners.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: shaazlarik
 * Author URI: https://github.com/shaazlarik
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lwa-exams
 * Update URI: https://github.com/shaazlarik/lwa-exams
 * Icon: icon-256x256.png
 */

 

if (!defined('ABSPATH')) {
    exit();
}

// Define constants
define('LWA_EXAMS_PATH', plugin_dir_path(__FILE__));
define('LWA_EXAMS_URL', plugin_dir_url(__FILE__));
define('LWA_EXAMS_VERSION', '1.0.0');

// 🔄 GitHub Plugin Update Checker
require_once LWA_EXAMS_PATH . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/shaazlarik/lwa-exams/', // 🔁 Replace with your GitHub repo
    __FILE__,
    'lwa-exams'
);

// // Optional: Enable use of GitHub release assets and changelog
$myUpdateChecker->getVcsApi()->enableReleaseAssets();
if (method_exists($myUpdateChecker, 'setChangelogFilename')) {
    $myUpdateChecker->setChangelogFilename('readme.txt');
}


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
            'questions'
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
