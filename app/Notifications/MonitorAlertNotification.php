<?php

namespace App\Notifications;

use App\Monitoring\AlertEvent;
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

        return $this->message->event === AlertEvent::Down
            ? $mail->error()
            : $mail;
    }
}
