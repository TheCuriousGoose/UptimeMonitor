<?php

namespace App\Notifications;

use App\Monitoring\AlertMessage;
use App\Monitoring\RenderedAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonitorAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly AlertMessage $message,
        public readonly RenderedAlert $text,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->text->title)
            ->greeting($this->text->title)
            ->line($this->text->body)
            ->action('View monitor', route('monitors.show', $this->message->monitor));

        // Severity, not `=== Down`: a reminder about a still-open outage is
        // every bit as urgent as the alert that opened it.
        return $this->message->event->severity() === 'error'
            ? $mail->error()
            : $mail;
    }
}
