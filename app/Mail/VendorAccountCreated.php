<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $created_vendor_name;
    public $vendor_name;
    public $created_vendor_email;
    /**
     * Create a new message instance.
     */
    public function __construct(string $created_vendor_name, string $created_vendor_email, string $vendor_name)
    {
        $this->created_vendor_name = $created_vendor_name;
        $this->vendor_name = $vendor_name;
        $this->created_vendor_email = $created_vendor_email;
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
            view: 'mail.vendor_account_created_by_admin',
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
