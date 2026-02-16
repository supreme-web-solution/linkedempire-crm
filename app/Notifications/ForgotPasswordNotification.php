<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ForgotPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public $userInfo
    )
    {}

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
        $message = (new MailMessage)
            ->subject('Password Reset')
            ->line($this->userInfo['name'].',')
            ->line('Your new password is: '. $this->userInfo['password']);
        
        // If email is provided (for test/admin notifications), include it
        if (isset($this->userInfo['email'])) {
            $message->line('User Email: '. $this->userInfo['email']);
        }
        
        return $message
            ->action('Login', url('/'))
            ->line('Please do not share this password with anybody.')
            ->line('If you did not initiate this request, kindly login to update your password.');
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
