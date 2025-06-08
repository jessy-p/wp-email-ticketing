<?php
class WP_Email_Ticketing_Responder {
    public static function send_reply_notification($ticket_id, $reply, $agent_id) {
        $customer_email = get_post_meta($ticket_id, 'customer_email', true);
        if (!$customer_email) {
            return false;
        }
        $agent = get_userdata($agent_id);
        $agent_name = $agent ? $agent->display_name : __('Support Agent', 'wp-email-ticketing');
        $subject = sprintf(
            '[Ticket #%d] %s',
            $ticket_id,
            sprintf(__('Your support ticket "%s" has a new reply', 'wp-email-ticketing'), get_the_title($ticket_id))
        );
        $message = sprintf(
            "Hello,\n\nYou have a new reply to your ticket:\n\n%s\n\nBest regards,\n%s",
            $reply,
            $agent_name
        );
        return wp_mail($customer_email, $subject, $message);
    }
} 