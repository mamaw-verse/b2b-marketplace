<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SourcingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class SourcingRequestAcknowledgment extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SourcingRequest $sourcingRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your product sourcing request',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.sourcing-request-acknowledgment',
            with: [
                'sourcingRequest' => $this->sourcingRequest,
            ],
        );
    }
}
