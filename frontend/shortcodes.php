<?php
if (!defined('ABSPATH')) {
    exit;
}

// This file is just for reference - the shortcodes are already registered in the main class
// We can add additional shortcode-related functions here if needed

function lwa_exams_register_frontend_shortcodes() {
    // No need to register here since they're in the main class
    // This is just a placeholder if we need additional shortcode functionality
}

add_action('init', 'lwa_exams_register_frontend_shortcodes');