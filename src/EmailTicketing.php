<?php

declare(strict_types=1);

namespace WPEmailTicketing;

use WPEmailTicketing\Ticket\TicketPostType;
use WPEmailTicketing\Ticket\TicketMeta;
use WPEmailTicketing\RestApi\TicketController;
use WPEmailTicketing\RestApi\WebhookController;
use WPEmailTicketing\Providers\PostmarkProvider;
use WPEmailTicketing\Providers\EmailProviderInterface;

class EmailTicketing {
    private static ?self $instance = null;
    private EmailProviderInterface $email_provider;
    
    /**
     * Get singleton instance.
     *
     * @return self
     */
    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->email_provider = new PostmarkProvider();
    }
    
    /**
     * Initialize plugin hooks and components.
     */
    public function init(): void {
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_meta']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Register ticket post types and taxonomies.
     */
    public function register_post_types(): void {
        TicketPostType::register();
    }
    
    /**
     * Register ticket meta fields.
     */
    public function register_meta(): void {
        TicketMeta::register();
    }
    
    /**
     * Register REST API routes.
     */
    public function register_rest_routes(): void {
        $ticket_controller = new TicketController($this->email_provider);
        $webhook_controller = new WebhookController($this->email_provider);
        
        $ticket_controller->register_routes();
        $webhook_controller->register_routes();
    }
    
    /**
     * Plugin activation handler.
     */
    public function activate(): void {
        $this->register_post_types();
        TicketPostType::maybe_insert_default_terms();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation handler.
     */
    public function deactivate(): void {
        flush_rewrite_rules();
    }
}