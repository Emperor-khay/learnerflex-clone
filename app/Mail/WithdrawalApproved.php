<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $withdrawalAmountInNaira;
    public $type;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct($withdrawalAmountInNaira, $type, $user)
    {
        $this->withdrawalAmountInNaira = $withdrawalAmountInNaira;
        $this->type = $type;
        $this->user = $user;
    }
    public function envelope(): Envelope
    {
        $typeLabel = ucfirst($this->type);
        return new Envelope(
            subject: "Your Payout has been processed 💸🤑",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.withdrawal_approved',
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
