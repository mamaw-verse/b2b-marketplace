<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CertificationApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CertificationInterestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CertificationApplication $application,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $applicant = $this->applicantName();

        $message = (new MailMessage)
            ->subject("{$applicant} is interested in Halal Certification")
            ->line("{$applicant} has expressed interest in applying for external Halal Certification.")
            ->action('View Certification Interests', route('admin.certifications.index'));

        if ($this->application->document_path !== null) {
            $message->attachFromStorageDisk(
                's3',
                $this->application->document_path,
                $this->attachmentName(),
                ['mime' => (string) Storage::disk('s3')->mimeType($this->application->document_path)],
            );
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $applicant = $this->applicantName();

        return [
            'type' => 'certification_interest',
            'application_id' => $this->application->id,
            'shop_id' => $this->application->shop_id,
            'applicant_name' => $applicant,
            'email' => $this->application->email,
            'message' => "{$applicant} is interested in Halal Certification.",
            'url' => route('admin.certifications.index'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    private function applicantName(): string
    {
        return $this->application->company_name
            ?? $this->application->shop?->storefront_name
            ?? 'A company';
    }

    private function attachmentName(): string
    {
        $slug = Str::slug($this->applicantName(), '_');
        $extension = Str::afterLast($this->application->document_path ?? '', '.');

        return "letter_of_intent_{$slug}.{$extension}";
    }
}
