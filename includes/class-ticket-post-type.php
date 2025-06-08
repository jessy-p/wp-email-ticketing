<?php
class WP_Email_Ticketing_Post_Type {
    public static function register() {
        // Register Ticket CPT
        register_post_type('ticket', [
            'labels' => [
                'name' => __('Tickets', 'wp-email-ticketing'),
                'singular_name' => __('Ticket', 'wp-email-ticketing'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'comments'],
            'has_archive' => false,
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-tickets',
        ]);

        // Register Status Taxonomy
        register_taxonomy('ticket_status', 'ticket', [
            'labels' => [
                'name' => __('Ticket Statuses', 'wp-email-ticketing'),
                'singular_name' => __('Ticket Status', 'wp-email-ticketing'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'hierarchical' => false,
        ]);

        // Register Priority Taxonomy
        register_taxonomy('ticket_priority', 'ticket', [
            'labels' => [
                'name' => __('Ticket Priorities', 'wp-email-ticketing'),
                'singular_name' => __('Ticket Priority', 'wp-email-ticketing'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'hierarchical' => false,
        ]);
    }

    public static function maybe_insert_default_terms() {
        // Insert default statuses
        $statuses = ['Open', 'Pending', 'Closed'];
        foreach ($statuses as $status) {
            if (!term_exists($status, 'ticket_status')) {
                wp_insert_term($status, 'ticket_status');
            }
        }
        // Insert default priorities
        $priorities = ['Low', 'Medium', 'High'];
        foreach ($priorities as $priority) {
            if (!term_exists($priority, 'ticket_priority')) {
                wp_insert_term($priority, 'ticket_priority');
            }
        }
    }
}

// Register CPT and taxonomies on init
add_action('init', ['WP_Email_Ticketing_Post_Type', 'register']); 