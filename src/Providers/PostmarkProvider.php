<?php

declare(strict_types=1);

namespace WPEmailTicketing\Providers;

use WPEmailTicketing\DTO\EmailMessage;

class PostmarkProvider implements EmailProviderInterface {
    private const SUPPORT_EMAIL = 'support@example.com';
    
    /**
     * Parse Postmark webhook data into EmailMessage DTO.
     *
     * @param array $data Raw webhook data from Postmark.
     * @return EmailMessage Parsed email message.
     */
    public function parse_incoming_email(array $data): EmailMessage {
        $email_message = new EmailMessage(
            from: $data['From'] ?? '',
            subject: $data['Subject'] ?? '',
            body: !empty($data['TextBody']) ? $data['TextBody'] : ($data['HtmlBody'] ?? ''),
            from_name: $data['FromName'] ?? '',
            attachments: $this->parse_attachments($data['Attachments'] ?? [])
        );

        $email_message->extract_ticket_reference();
        
        return $email_message;
    }

    /**
     * Send notification email with proper headers.
     *
     * @param string $to Recipient email address.
     * @param string $subject Email subject.
     * @param string $message Email message body.
     * @return bool True on success, false on failure.
     */
    public function send_notification(string $to, string $subject, string $message): bool {
        $headers = [
            'From: Support <' . self::SUPPORT_EMAIL . '>',
            'Reply-To: ' . self::SUPPORT_EMAIL,
        ];
        
        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Parse and validate Postmark attachments.
     *
     * @param array $attachments Raw attachment data from webhook.
     * @return array Processed attachment data.
     */
    private function parse_attachments(array $attachments): array {
        if (empty($attachments) || !is_array($attachments)) {
            return [];
        }

        $parsed = [];
        $attachments = array_slice($attachments, 0, 10); // Security: limit attachments
        
        foreach ($attachments as $attachment) {
            if (empty($attachment['Name']) || empty($attachment['Content'])) {
                continue;
            }
            
            $filename = sanitize_file_name($attachment['Name']);
            $filetype = wp_check_filetype($filename);
            
            if (!$filetype['ext'] || !$filetype['type']) {
                continue;
            }
            
            $file_data = base64_decode($attachment['Content']);
            if ($file_data === false) {
                continue;
            }
            
            // Security: file size limit
            if (strlen($file_data) > 5 * 1024 * 1024) {
                continue;
            }
            
            $parsed[] = [
                'name' => $filename,
                'content' => $file_data,
                'type' => $filetype['type'],
            ];
        }
        
        return $parsed;
    }
}