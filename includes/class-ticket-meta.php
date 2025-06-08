<?php
class WP_Email_Ticketing_Meta {
    public static function register() {
        // Customer Email
        register_post_meta('ticket', 'customer_email', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_email',
            'auth_callback' => function() { return current_user_can('edit_posts'); },
        ]);
        // Customer Name
        register_post_meta('ticket', 'customer_name', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function() { return current_user_can('edit_posts'); },
        ]);
    }
}
add_action('init', ['WP_Email_Ticketing_Meta', 'register']); 