<?php
// Admin UI for WP Email Ticketing
add_action('admin_menu', function() {
    add_menu_page(
        __('Tickets', 'wp-email-ticketing'),
        __('Tickets', 'wp-email-ticketing'),
        'edit_posts',
        'wp-email-ticketing',
        'wp_email_ticketing_admin_page',
        'dashicons-tickets',
        26
    );
});

// Enqueue scripts and styles for the admin page
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'toplevel_page_wp-email-ticketing') {
        return;
    }
    
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    $assets_path = plugin_dir_path(dirname(__FILE__)) . 'dist/assets/';
    
    // Enqueue built assets if they exist
    if (is_dir($assets_path)) {
        $assets = glob($assets_path . '*.css');
        foreach ($assets as $asset) {
            $filename = basename($asset);
            wp_enqueue_style('wp-email-ticketing-' . $filename, $plugin_url . 'dist/assets/' . $filename);
        }
        
        $assets = glob($assets_path . '*.js');
        foreach ($assets as $asset) {
            $filename = basename($asset);
            $handle = 'wp-email-ticketing-' . $filename;
            wp_enqueue_script($handle, $plugin_url . 'dist/assets/' . $filename, [], null, true);
            
            // Localize script with API settings
            wp_localize_script($handle, 'wpApiSettings', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);
        }
    }
});

function wp_email_ticketing_admin_page() {
    // Handle form submissions first
    if (isset($_POST['wp_email_ticketing_update_status']) && isset($_POST['wp_email_ticketing_status']) && isset($_GET['ticket_id'])) {
        $ticket_id = intval($_GET['ticket_id']);
        $new_status = sanitize_text_field($_POST['wp_email_ticketing_status']);
        wp_set_object_terms($ticket_id, $new_status, 'ticket_status');
        echo '<div class="updated"><p>' . esc_html__('Status updated.', 'wp-email-ticketing') . '</p></div>';
    }
    
    if (isset($_POST['wp_email_ticketing_add_reply']) && !empty($_POST['wp_email_ticketing_reply']) && isset($_GET['ticket_id'])) {
        $ticket_id = intval($_GET['ticket_id']);
        $reply = sanitize_textarea_field($_POST['wp_email_ticketing_reply']);
        wp_insert_comment([
            'comment_post_ID' => $ticket_id,
            'comment_content' => $reply,
            'user_id' => get_current_user_id(),
            'comment_author' => wp_get_current_user()->display_name,
            'comment_approved' => 1,
        ]);
        $email_provider = new \WPEmailTicketing\Providers\PostmarkProvider();
        $customer_email = get_post_meta($ticket_id, 'customer_email', true);
        if ($customer_email) {
            $subject = sprintf(
                '[Ticket #%d] %s',
                $ticket_id,
                sprintf(__('Your support ticket "%s" has a new reply', 'wp-email-ticketing'), get_the_title($ticket_id))
            );
            $email_provider->send_notification($customer_email, $subject, $reply);
        }
        echo '<div class="updated"><p>' . esc_html__('Reply added.', 'wp-email-ticketing') . '</p></div>';
    }
    
    // Output container for React app
    echo '<div class="wrap">';
    echo '<div id="wp-email-ticketing-root"></div>';
    echo '</div>';
} 