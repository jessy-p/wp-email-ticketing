<?php
class WP_Email_Ticketing_REST_API {
    public static function register_routes() {
        register_rest_route('ticketing/v1', '/ticket', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_create_ticket'],
            'permission_callback' => '__return_true', // temporary fix, as my hosting doesn't seem to support auth in URL
        ]);
        // List tickets
        register_rest_route('ticketing/v1', '/tickets', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_list_tickets'],
            'permission_callback' => [__CLASS__, 'editor_permission_callback'],
        ]);
        // Ticket details
        register_rest_route('ticketing/v1', '/ticket/(?P<id>\\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'handle_ticket_details'],
            'permission_callback' => [__CLASS__, 'editor_permission_callback'],
        ]);
        // Update status
        register_rest_route('ticketing/v1', '/ticket/(?P<id>\\d+)/status', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_update_status'],
            'permission_callback' => [__CLASS__, 'editor_permission_callback'],
        ]);
        // Send reply
        register_rest_route('ticketing/v1', '/ticket/(?P<id>\\d+)/reply', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_send_reply'],
            'permission_callback' => [__CLASS__, 'editor_permission_callback'],
        ]);
    }

    public static function editor_permission_callback() {
        return current_user_can('edit_others_posts');
    }

    public static function handle_create_ticket($request) {
        $data = $request->get_json_params();
        // Validate required fields
        if (empty($data['Subject']) || empty($data['From']) || (empty($data['TextBody']) && empty($data['HtmlBody']))) {
            return new WP_Error('missing_fields', 'Required fields are missing', ['status' => 400]);
        }
        $subject = $data['Subject'];
        // Check for [Ticket #ID] in subject
        if (preg_match('/\[Ticket #(\d+)\]/', $subject, $matches)) {
            $ticket_id = intval($matches[1]);
            $ticket = get_post($ticket_id);
            if ($ticket && $ticket->post_type === 'ticket') {
                // Add as comment
                wp_insert_comment([
                    'comment_post_ID' => $ticket_id,
                    'comment_content' => !empty($data['TextBody']) ? $data['TextBody'] : $data['HtmlBody'],
                    'comment_author' => !empty($data['FromName']) ? $data['FromName'] : $data['From'],
                    'comment_author_email' => $data['From'],
                    'comment_approved' => 1,
                ]);
                // If status is Pending, change to Open
                $status_terms = get_the_terms($ticket_id, 'ticket_status');
                if ($status_terms && !is_wp_error($status_terms) && strtolower($status_terms[0]->name) === 'pending') {
                    wp_set_object_terms($ticket_id, 'Open', 'ticket_status');
                }
                return rest_ensure_response([
                    'success' => true,
                    'reply_added' => true,
                    'ticket_id' => $ticket_id,
                ]);
            }
        }
        // Prepare post data
        $postarr = [
            'post_type'    => 'ticket',
            'post_title'   => sanitize_text_field($data['Subject']),
            'post_content' => !empty($data['TextBody']) ? $data['TextBody'] : $data['HtmlBody'],
            'post_status'  => 'publish',
        ];
        $ticket_id = wp_insert_post($postarr);
        if (is_wp_error($ticket_id)) {
            return new WP_Error('insert_failed', 'Could not create ticket', ['status' => 500]);
        }
        // Set meta fields
        update_post_meta($ticket_id, 'customer_email', sanitize_email($data['From']));
        if (!empty($data['FromName'])) {
            update_post_meta($ticket_id, 'customer_name', sanitize_text_field($data['FromName']));
        }
        // Set status taxonomy (default Open)
        wp_set_object_terms($ticket_id, !empty($data['Status']) ? $data['Status'] : 'Open', 'ticket_status');
        // Set priority taxonomy (default Medium)
        wp_set_object_terms($ticket_id, !empty($data['Priority']) ? $data['Priority'] : 'Medium', 'ticket_priority');
        // Handle attachments if present
        if (!empty($data['Attachments']) && is_array($data['Attachments'])) {
            foreach ($data['Attachments'] as $attachment) {
                if (empty($attachment['Name']) || empty($attachment['Content'])) {
                    continue;
                }
                $filename = sanitize_file_name($attachment['Name']);
                $filetype = wp_check_filetype($filename);
                $upload_dir = wp_upload_dir();
                $file_data = base64_decode($attachment['Content']);
                if ($file_data === false) {
                    continue;
                }
                $file_path = $upload_dir['path'] . '/' . $filename;
                file_put_contents($file_path, $file_data);
                $attachment_post = [
                    'post_mime_type' => !empty($attachment['ContentType']) ? $attachment['ContentType'] : $filetype['type'],
                    'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                    'post_parent'    => $ticket_id,
                ];
                $attach_id = wp_insert_attachment($attachment_post, $file_path, $ticket_id);
                // Generate attachment metadata
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                wp_update_attachment_metadata($attach_id, $attach_data);
            }
        }
        // Return response
        return rest_ensure_response([
            'success' => true,
            'ticket_id' => $ticket_id,
        ]);
    }

    public static function handle_list_tickets($request) {
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
        $query = new WP_Query($args);
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

    public static function handle_ticket_details($request) {
        $id = intval($request['id']);
        $post = get_post($id);
        if (!$post || $post->post_type !== 'ticket') {
            return new WP_Error('not_found', 'Ticket not found', ['status' => 404]);
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

        // Add the original post as the first entry in the conversation
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

    public static function handle_update_status($request) {
        $id = intval($request['id']);
        $post = get_post($id);
        if (!$post || $post->post_type !== 'ticket') {
            return new WP_Error('not_found', 'Ticket not found', ['status' => 404]);
        }
        $status = sanitize_text_field($request->get_param('status'));
        if (!$status) {
            return new WP_Error('missing_status', 'Status is required', ['status' => 400]);
        }
        wp_set_object_terms($id, $status, 'ticket_status');
        return rest_ensure_response(['success' => true, 'ticket_id' => $id, 'status' => $status]);
    }

    public static function handle_send_reply($request) {
        $id = intval($request['id']);
        $post = get_post($id);
        if (!$post || $post->post_type !== 'ticket') {
            return new WP_Error('not_found', 'Ticket not found', ['status' => 404]);
        }
        $message = sanitize_textarea_field($request->get_param('message'));
        if (!$message) {
            return new WP_Error('missing_message', 'Message is required', ['status' => 400]);
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
        if (class_exists('WP_Email_Ticketing_Responder')) {
            WP_Email_Ticketing_Responder::send_reply_notification($id, $message, $user->ID);
        }
        return rest_ensure_response(['success' => true, 'ticket_id' => $id, 'comment_id' => $comment_id]);
    }
}

