<?php
// lwa-Exams/uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Load the main plugin file to access the classes
require_once __DIR__ . '/lwa-exams.php';

// Perform uninstallation via the Dashboard class
LWA_EXAMS_Dashboard::uninstall();