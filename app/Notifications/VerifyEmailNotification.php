<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

final class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Build the mail representation of the notification using the branded
     * 1Halal Store verification template.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $firstName = $notifiable->first_name ?? null;
        $firstName = is_string($firstName) && mb_trim($firstName) !== '' ? mb_trim($firstName) : null;

        return (new MailMessage)
            ->subject('Verify your email address — '.config('app.name'))
            ->view('emails.verify-email', [
                'url' => $verificationUrl,
                'firstName' => $firstName,
            ]);
    }
}
