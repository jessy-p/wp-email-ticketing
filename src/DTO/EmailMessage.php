<?php

declare(strict_types=1);

namespace WPEmailTicketing\DTO;

class EmailMessage
{
    public function __construct(
        public string $from,
        public string $subject,
        public string $body,
        public string $from_name = '',
        public array $attachments = [],
        public bool $is_reply = false,
        public ?int $ticket_id = null
    ) {
    }

    /**
     * Extract ticket ID from subject line if it's a reply.
     */
    public function extract_ticket_reference(): void
    {
        if (preg_match('/\[Ticket #(\d+)\]/', $this->subject, $matches)) {
            $this->is_reply = true;
            $this->ticket_id = (int) $matches[1];
        }
    }
}
