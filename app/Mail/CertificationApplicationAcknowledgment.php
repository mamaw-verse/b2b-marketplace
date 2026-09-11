<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\CertificationApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class CertificationApplicationAcknowledgment extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public CertificationApplication $application,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('We received your halal certification application'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.certification-application-acknowledgment',
            with: [
                'application' => $this->application,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->application->document_path === null) {
            return [];
        }

        $slug = Str::slug($this->application->company_name ?? 'applicant', '_');
        $extension = Str::afterLast($this->application->document_path, '.');

        return [
            Attachment::fromStorageDisk('s3', $this->application->document_path)
                ->as("letter_of_intent_{$slug}.{$extension}"),
        ];
    }
}
