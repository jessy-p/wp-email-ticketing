<?php

declare(strict_types=1);

namespace WPEmailTicketing\Providers;

use WPEmailTicketing\DTO\EmailMessage;

interface EmailProviderInterface {
    /**
     * Parse incoming webhook data into standardized EmailMessage.
     *
     * @param array $webhook_data Raw webhook data from email provider.
     * @return EmailMessage Parsed email message.
     */
    public function parse_incoming_email(array $webhook_data): EmailMessage;

    /**
     * Send email notification to customer.
     *
     * @param string $to Recipient email address.
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return bool True on success, false on failure.
     */
    public function send_notification(string $to, string $subject, string $message): bool;
}