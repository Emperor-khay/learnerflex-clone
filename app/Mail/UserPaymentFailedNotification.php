<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserPaymentFailedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $amount;
    public $message;

    /**
     * Create a new message instance.
     */
    public function __construct($transaction, $amount, $message)
    {
        $this->transaction = $transaction;
        $this->amount = $amount;
        $this->message = $message;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Payment Failed')
                    ->to($this->transaction->email)
                    ->view('mail.user_payment_failed_notification');
    }
}
