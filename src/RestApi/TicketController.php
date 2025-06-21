<?php

declare(strict_types=1);

namespace WPEmailTicketing\RestApi;

use WPEmailTicketing\Providers\PostmarkProvider;
use WPEmailTicketing\Providers\EmailProviderInterface;

class TicketController
{
    private EmailProviderInterface $email_provider;

    public function __construct(EmailProviderInterface $email_provider)
    {
        $this->email_provider = $email_provider;
    }

    /**
     * Register ticket management REST API routes.
     */
    public function register_routes(): void
    {
        register_rest_route('ticketing/v1', '/tickets', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_list_tickets'],
            'permission_callback' => [self::class, 'ticket_permission_callback'],
        ]);

        register_rest_route('ticketing/v1', '/ticket/(?P<id>\\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_ticket_details'],
            'permission_callback' => [self::class, 'ticket_permission_callback'],
        ]);

        register_rest_route('ticketing/v1', '/ticket/(?P<id>\\d+)/status', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_update_status'],
            'permission_callback' => [self::class, 'ticket_permission_callback'],
        ]);

        register_rest_route('ticketing/v1', '/ticket/(?P<id>\\d+)/reply', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_send_reply'],
            'permission_callback' => [self::class, 'ticket_permission_callback'],
        ]);
    }

    /**
     * Permission callback for ticket endpoints.
     */
    public static function ticket_permission_callback(): bool
    {
        return current_user_can('edit_others_posts');
    }

    /**
     * List tickets with filtering and pagination.
     */
    public function handle_list_tickets($request)
    {
        $args = [
            'post_type' => 'ticket',
            'post_status' => 'any',
            'posts_per_page' => $request->get_param('per_page') ?: 20,
            'paged' => $request->get_param('page') ?: 1,
        ];

        if ($status = $request->get_param('status')) {
            $args['tax_query'] = [[
                'taxonomy' => 'ticket_status',
                'field' => 'slug',
                'terms' => $status,
            ]];
        }

        if ($search = $request->get_param('search')) {
            $args['s'] = $search;
        }

        $query = new \WP_Query($args);
        $tickets = [];

        foreach ($query->posts as $post) {
            $status = wp_get_post_terms($post->ID, 'ticket_status', ['fields' => 'names']);
            $tickets[] = [
                'id' => $post->ID,
                'subject' => $post->post_title,
                'status' => !empty($status) ? $status[0] : '',
                'requester' => get_post_meta($post->ID, 'customer_email', true),
                'date' => $post->post_date,
            ];
        }

        return rest_ensure_response([
            'tickets' => $tickets,
            'total' => $query->found_posts,
            'page' => $args['paged'],
            'per_page' => $args['posts_per_page'],
        ]);
    }

    /**
     * Get detailed ticket information including conversation.
     */
    public function handle_ticket_details($request)
    {
        $id = intval($request['id']);
        $post = get_post($id);

        if (!$post || $post->post_type !== 'ticket') {
            return new \WP_Error('not_found', 'Ticket not found', ['status' => 404]);
        }

        $status = wp_get_post_terms($id, 'ticket_status', ['fields' => 'names']);
        $status = !empty($status) ? $status[0] : '';

        $priority = wp_get_post_terms($id, 'ticket_priority', ['fields' => 'names']);

        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_parent' => $id,
            'posts_per_page' => -1,
        ]);

        $attachment_list = [];
        foreach ($attachments as $a) {
            $attachment_list[] = [
                'id' => $a->ID,
                'name' => get_the_title($a->ID),
                'url' => wp_get_attachment_url($a->ID),
            ];
        }

        $comments = get_comments(['post_id' => $id, 'order' => 'ASC']);
        $conversation = [];

        // Add the original post as the first entry
        $conversation[] = [
            'author' => get_post_meta($id, 'customer_name', true) ?: get_post_meta($id, 'customer_email', true),
            'author_email' => get_post_meta($id, 'customer_email', true),
            'content' => $post->post_content,
            'date' => $post->post_date,
        ];

        foreach ($comments as $c) {
            $conversation[] = [
                'author' => $c->comment_author,
                'author_email' => $c->comment_author_email,
                'content' => $c->comment_content,
                'date' => $c->comment_date,
            ];
        }

        return rest_ensure_response([
            'id' => $id,
            'subject' => $post->post_title,
            'content' => $post->post_content,
            'status' => $status,
            'priority' => $priority,
            'requester' => get_post_meta($id, 'customer_email', true),
            'attachments' => $attachment_list,
            'conversation' => $conversation,
            'date' => $post->post_date,
        ]);
    }

    /**
     * Update ticket status.
     */
    public function handle_update_status($request)
    {
        $id = intval($request['id']);
        $post = get_post($id);

        if (!$post || $post->post_type !== 'ticket') {
            return new \WP_Error('not_found', 'Ticket not found', ['status' => 404]);
        }

        $status = sanitize_text_field($request->get_param('status'));
        if (!$status) {
            return new \WP_Error('missing_status', 'Status is required', ['status' => 400]);
        }

        wp_set_object_terms($id, $status, 'ticket_status');

        return rest_ensure_response([
            'success' => true,
            'ticket_id' => $id,
            'status' => $status
        ]);
    }

    /**
     * Send reply to ticket and notify customer.
     */
    public function handle_send_reply($request)
    {
        $id = intval($request['id']);
        $post = get_post($id);

        if (!$post || $post->post_type !== 'ticket') {
            return new \WP_Error('not_found', 'Ticket not found', ['status' => 404]);
        }

        $message = sanitize_textarea_field($request->get_param('message'));
        if (!$message) {
            return new \WP_Error('missing_message', 'Message is required', ['status' => 400]);
        }

        $user = wp_get_current_user();
        $comment_id = wp_insert_comment([
            'comment_post_ID' => $id,
            'comment_content' => $message,
            'user_id' => $user->ID,
            'comment_author' => $user->display_name,
            'comment_approved' => 1,
        ]);

        // Send email notification to customer
        $customer_email = get_post_meta($id, 'customer_email', true);
        if ($customer_email) {
            $subject = sprintf(
                '[Ticket #%d] %s',
                $id,
                sprintf(__('Your support ticket "%s" has a new reply', 'wp-email-ticketing'), get_the_title($id))
            );
            $this->email_provider->send_notification($customer_email, $subject, $message);
        }

        return rest_ensure_response([
            'success' => true,
            'ticket_id' => $id,
            'comment_id' => $comment_id
        ]);
    }
}
