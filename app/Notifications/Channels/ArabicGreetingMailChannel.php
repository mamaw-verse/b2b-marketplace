<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail channel that applies a shared Arabic salutation to every
 * notification email, addressing the recipient by their first name.
 *
 * Centralizing the greeting here means individual notification classes
 * do not need to call ->greeting() themselves, so all current and future
 * mail notifications stay consistent.
 */
final class ArabicGreetingMailChannel extends MailChannel
{
    /**
     * Send the given notification, injecting the Arabic greeting when the
     * notification produces a Markdown MailMessage without its own greeting.
     *
     * This mirrors the parent implementation but decorates the built
     * message before dispatch, so toMail() is only invoked once.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Mail\SentMessage|null
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toMail($notifiable);

        if (! $notifiable->routeNotificationFor('mail', $notification) &&
            ! $message instanceof Mailable) {
            return null;
        }

        if ($message instanceof Mailable) {
            return $message->send($this->mailer);
        }

        if ($message instanceof MailMessage && empty($message->greeting)) {
            $message->greeting($this->greetingFor($notifiable));
        }

        return $this->mailer->mailer($message->mailer ?? null)->send(
            $this->buildView($message),
            array_merge($message->data(), $this->additionalMessageData($notification)),
            $this->messageBuilder($notifiable, $notification, $message),
        );
    }

    /**
     * Build the Arabic salutation, including the recipient's first name
     * when it is available on the notifiable.
     *
     * @param  mixed  $notifiable
     */
    private function greetingFor($notifiable): string
    {
        $firstName = $notifiable->first_name ?? null;

        if (is_string($firstName)) {
            $firstName = mb_trim($firstName);
        }

        return is_string($firstName) && $firstName !== ''
            ? "Assalamu alaikum, {$firstName},"
            : 'Assalamu alaikum,';
    }
}
