<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $password,
        public string $role
    ) {
        //
    }

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
        $loginUrl = url('/login');

        $message = (new MailMessage)
            ->subject('Welcome to Smart Fish Management System')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your account has been created successfully.')
            ->line('**Account Details:**');

        if ($notifiable->email) {
            $message->line('Email: '.$notifiable->email);
        }
        if ($notifiable->username) {
            $message->line('Username: '.$notifiable->username.' (use this to log in if you do not use email)');
        }

        return $message
            ->line('Role: '.ucfirst($this->role))
            ->line('Temporary Password: **'.$this->password.'**')
            ->line('Please keep this password safe and change it after your first login.')
            ->action('Login to Your Account', $loginUrl)
            ->line('If you have any questions, please contact your administrator.')
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
