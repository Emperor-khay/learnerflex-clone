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
    public function __construct($argument, $argument2, $argument3 )
    {
        $this->affiliate_name = $argument;
        $this->vendor_name = $argument2;
        $this->affiliate_email = $argument3;
        
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Affiliate Account Created',
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
