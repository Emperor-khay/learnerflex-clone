<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $affiliate_name;
    public $vendor_name;
    public $affiliate_email;

    /**
     * Create a new message instance.
     */
    public function __construct(string $affiliate_name, string $vendor_name, string $affiliate_email)
    {
        $this->affiliate_name = $affiliate_name;
        $this->vendor_name = $vendor_name;
        $this->affiliate_email = $affiliate_email;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You just got an account with us',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.affiliate_account_created_by_admin',
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
