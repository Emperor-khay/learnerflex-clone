<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationIssueNotification extends Notification
{
    use Queueable;

    protected $email;
    protected $orderID;

    public function __construct($email, $orderID)
    {
        $this->email = $email;
        $this->orderID = $orderID;
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
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Registration Issue Notification')
            ->line('There was an issue with registration for the email: ' . $this->email)
            ->line('Order ID: ' . $this->orderID)
            ->action('View Registrations', url('/admin/registrations')) // Replace with your admin URL
            ->line('Please check the registration data.');
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
