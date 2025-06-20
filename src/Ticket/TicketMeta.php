<?php

declare(strict_types=1);

namespace WPEmailTicketing\Ticket;

class TicketMeta {
    /**
     * Register ticket meta fields.
     */
    public static function register(): void {
        register_post_meta('ticket', 'customer_email', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_email',
            'auth_callback' => function() { return current_user_can('edit_posts'); },
        ]);
        
        register_post_meta('ticket', 'customer_name', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function() { return current_user_can('edit_posts'); },
        ]);
    }
}