<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\Channels\ArabicGreetingMailChannel;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;

test('the notification mail channel is overridden with the Arabic greeting channel', function () {
    $channel = app(ChannelManager::class)->driver('mail');

    expect($channel)->toBeInstanceOf(ArabicGreetingMailChannel::class);
});

test('markdown notifications are addressed with an Arabic salutation and the first name', function () {
    $user = User::factory()->create(['first_name' => 'Aisha']);

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('Test')
                ->line('Your order has been shipped.');
        }
    };

    $captured = null;
    Event::listen(
        MessageSending::class,
        function ($event) use (&$captured) {
            $captured = $event->message->getHtmlBody();

            return false; // prevent actual transport
        }
    );

    $user->notify($notification);

    expect($captured)->toContain('Assalamu alaikum, Aisha,')
        ->and($captured)->not->toMatch('/<h1[^>]*>\s*Assalamu/i');
});

test('the salutation omits the name gracefully when the notifiable has none', function () {
    $notifiable = new class
    {
        use Notifiable;

        public function routeNotificationForMail(): string
        {
            return 'nobody@example.com';
        }
    };

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)->subject('Test')->line('Hello there.');
        }
    };

    $captured = null;
    Event::listen(
        MessageSending::class,
        function ($event) use (&$captured) {
            $captured = $event->message->getHtmlBody();

            return false;
        }
    );

    $notifiable->notify($notification);

    expect($captured)->toContain('Assalamu alaikum,')
        ->and($captured)->not->toContain('Assalamu alaikum, ,');
});

test('the branded verification email greets the user by first name', function () {
    $user = User::factory()->unverified()->create(['first_name' => 'Yusuf']);

    $rendered = (string) (new VerifyEmailNotification)->toMail($user)->render();

    expect($rendered)->toContain('Assalamu alaikum, Yusuf,');
});
