<?php

declare(strict_types=1);

namespace WPEmailTicketing\RestApi;

use WPEmailTicketing\Providers\PostmarkProvider;
use WPEmailTicketing\Providers\EmailProviderInterface;
use WPEmailTicketing\DTO\EmailMessage;

class WebhookController
{
    private EmailProviderInterface $email_provider;

    public function __construct(EmailProviderInterface $email_provider)
    {
        $this->email_provider = $email_provider;
    }

    /**
     * Register webhook REST API routes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            'ticketing/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_incoming_email'],
            'permission_callback' => [self::class, 'ticket_permission_callback'],
            ]
        );
    }

    /**
     * Permission callback for ticket endpoints.
     *
     * @return bool True if user can edit posts.
     */
    public static function ticket_permission_callback(): bool
    {
        return current_user_can('edit_others_posts');
    }

    /**
     * Handle incoming email webhook and create/update tickets.
     *
     * @param  \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response Response object.
     */
    public function handle_incoming_email($request)
    {
        $data = $request->get_json_params();
        $email_message = $this->email_provider->parse_incoming_email($data);
        
        if (!$this->validate_email_message($email_message)) {
            return rest_ensure_response(['success' => true, 'skipped' => 'invalid_email']);
        }

        if ($email_message->is_reply && $email_message->ticket_id) {
            return $this->handle_ticket_reply($email_message);
        }

        return $this->create_new_ticket($email_message);
    }

    /**
     * Validate email message data.
     *
     * @param  EmailMessage $email Email message to validate.
     * @return bool True if valid, false otherwise.
     */
    private function validate_email_message(EmailMessage $email): bool
    {
        if (empty($email->subject) || empty($email->from) || empty($email->body)) {
            return false;
        }

        if (!is_email($email->from)) {
            return false;
        }

        // Security: Skip if content is too long
        if (strlen($email->body) > 50000) {
            return false;
        }

        return true;
    }

    /**
     * Handle reply to existing ticket.
     *
     * @param  EmailMessage $email Email message.
     * @return \WP_REST_Response Response object.
     */
    private function handle_ticket_reply(EmailMessage $email)
    {
        $ticket = get_post($email->ticket_id);
        if (!$ticket || $ticket->post_type !== 'ticket') {
            return $this->create_new_ticket($email);
        }

        wp_insert_comment(
            [
            'comment_post_ID' => $email->ticket_id,
            'comment_content' => $email->body,
            'comment_author' => !empty($email->from_name) ? $email->from_name : $email->from,
            'comment_author_email' => $email->from,
            'comment_approved' => 1,
            ]
        );

        $status_terms = get_the_terms($email->ticket_id, 'ticket_status');
        if ($status_terms && !is_wp_error($status_terms) && strtolower($status_terms[0]->name) === 'pending') {
            wp_set_object_terms($email->ticket_id, 'Open', 'ticket_status');
        }

        return rest_ensure_response(
            [
            'success' => true,
            'reply_added' => true,
            'ticket_id' => $email->ticket_id,
            ]
        );
    }

    /**
     * Create new ticket from email.
     *
     * @param  EmailMessage $email Email message.
     * @return \WP_REST_Response Response object.
     */
    private function create_new_ticket(EmailMessage $email)
    {
        $subject = strlen($email->subject) > 200 ? substr($email->subject, 0, 200) : $email->subject;

        $postarr = [
            'post_type'    => 'ticket',
            'post_title'   => sanitize_text_field($subject),
            'post_content' => $email->body,
            'post_status'  => 'publish',
        ];

        $ticket_id = wp_insert_post($postarr);
        if (is_wp_error($ticket_id)) {
            return rest_ensure_response(['success' => true, 'skipped' => 'creation_failed']);
        }

        update_post_meta($ticket_id, 'customer_email', sanitize_email($email->from));
        if (!empty($email->from_name)) {
            update_post_meta($ticket_id, 'customer_name', sanitize_text_field($email->from_name));
        }

        wp_set_object_terms($ticket_id, 'Open', 'ticket_status');
        wp_set_object_terms($ticket_id, 'Medium', 'ticket_priority');

        $this->process_attachments($email->attachments, $ticket_id);

        return rest_ensure_response(
            [
            'success' => true,
            'ticket_id' => $ticket_id,
            ]
        );
    }

    /**
     * Process and save email attachments.
     *
     * @param array $attachments Array of attachment data.
     * @param int   $ticket_id   Ticket ID to attach files to.
     */
    private function process_attachments(array $attachments, int $ticket_id): void
    {
        foreach ($attachments as $attachment) {
            $upload_dir = wp_upload_dir();
            if ($upload_dir['error']) {
                continue;
            }

            $filename = wp_unique_filename($upload_dir['path'], $attachment['name']);
            $file_path = $upload_dir['path'] . '/' . $filename;

            if (file_put_contents($file_path, $attachment['content']) === false) {
                continue;
            }

            $attachment_post = [
                'post_mime_type' => $attachment['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'post_parent'    => $ticket_id,
            ];

            $attach_id = wp_insert_attachment($attachment_post, $file_path, $ticket_id);
            if (!is_wp_error($attach_id)) {
                include_once ABSPATH . 'wp-admin/includes/image.php';
                $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                wp_update_attachment_metadata($attach_id, $attach_data);
            }
        }
    }
}
