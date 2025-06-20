<?php

declare(strict_types=1);

/*
Plugin Name: WP Email Ticketing
Description: Receives email JSON via webhook and creates/manages tickets in WordPress.
Version: 1.0.0
Author: jessy
*/

use WPEmailTicketing\EmailTicketing;

if (!defined('ABSPATH')) {
    exit;
}

define('WPET_VERSION', '1.0.0');
define('WPET_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPET_PLUGIN_URL', plugin_dir_url(__FILE__));

if (file_exists(WPET_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WPET_PLUGIN_DIR . 'vendor/autoload.php';
}

register_activation_hook(__FILE__, [EmailTicketing::instance(), 'activate']);
register_deactivation_hook(__FILE__, [EmailTicketing::instance(), 'deactivate']);

EmailTicketing::instance()->init();

if (is_admin()) {
    require_once WPET_PLUGIN_DIR . 'admin/admin-ui.php';
}