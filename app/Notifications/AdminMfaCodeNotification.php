<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminMfaCodeNotification extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly int $expirationMinutes,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your OJT Tracker administrator verification code')
            ->greeting('Administrator verification required')
            ->line("Your one-time verification code is: {$this->code}")
            ->line("This code expires in {$this->expirationMinutes} minutes and can only be used once.")
            ->line('If you did not attempt to sign in, reset your password and contact a system administrator.');
    }
}
