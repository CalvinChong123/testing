<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyAccountNotification extends Notification
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $web_domain = config('app.web_url');
        $url = $web_domain."/password/reset/{$this->token}?email={$notifiable->getEmailForPasswordReset()}";
        $count = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Account Verification')
            ->line('Thank you for joining us.')
            ->line('Click the link below to setup your account password.')
            ->action('Set My Password', $url)
            ->line("This invitation link will expire in {$count} minutes.")
            ->line('If you did not request for an invitation, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
        ];
    }
}
