<?php
/*
Plugin Name: WP Email Ticketing
Description: Receives JSON via REST API and creates/manages tickets in WordPress.
Version: 1.0.0
Author: Your Name
*/


if (!defined('ABSPATH')) exit;

define('WPET_VERSION', '1.0.0');
define('WPET_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPET_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WPET_PLUGIN_DIR . 'includes/class-ticket-post-type.php';
require_once WPET_PLUGIN_DIR . 'includes/class-ticket-meta.php';
require_once WPET_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once WPET_PLUGIN_DIR . 'includes/class-ticket-responder.php';

register_activation_hook(__FILE__, 'wp_email_ticketing_activate');
register_deactivation_hook(__FILE__, 'wp_email_ticketing_deactivate');

function wp_email_ticketing_activate() {
    // Register CPT and taxonomies so terms can be inserted
    WP_Email_Ticketing_Post_Type::register();
    WP_Email_Ticketing_Post_Type::maybe_insert_default_terms();
    flush_rewrite_rules();
}

function wp_email_ticketing_deactivate() {
    flush_rewrite_rules();
}

add_action('rest_api_init', 'wp_email_ticketing_init');
function wp_email_ticketing_init() {
    WP_Email_Ticketing_REST_API::register_routes();
}

if (is_admin()) {
    require_once WPET_PLUGIN_DIR . 'admin/admin-ui.php';
    // Enqueue admin assets, etc.
} 