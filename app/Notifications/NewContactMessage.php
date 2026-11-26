<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Emails the admin when someone uses the contact form. Replying to the
 * email answers the visitor directly.
 */
class NewContactMessage extends Notification
{
    use Queueable;

    public function __construct(public ContactMessage $contactMessage) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = $this->contactMessage;

        return (new MailMessage)
            ->subject(__('New message from :name', ['name' => $message->name]).' — '.$message->topicLabel())
            ->replyTo($message->email, $message->name)
            ->greeting(__('New message from :name', ['name' => $message->name]))
            ->line(__('Topic: :topic', ['topic' => $message->topicLabel()]))
            ->line(__('Email: :email', ['email' => $message->email]))
            ->line(Str::limit($message->message, 2000))
            ->action(__('Open messages'), route('admin.messages'))
            ->line(__('Reply to this email to answer :name.', ['name' => $message->name]));
    }
}
