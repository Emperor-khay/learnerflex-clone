<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalProcessingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $amount;

    public function __construct($name, $amount)
    {
        $this->name = $name;
        $this->amount = $amount;
    }
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your requested payment is on its way',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.withdrawal_processing',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
