<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EbookPurchaseSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user_name;
    public $product_name;
    public $product_access_link;
    public $download_link;
    public $mentor_name;

    public function __construct($user_name, $product_name, $product_access_link, $download_link, $mentor_name)
    {
        $this->user_name = $user_name;
        $this->product_name = $product_name;
        $this->product_access_link = $product_access_link;
        $this->download_link = $download_link;
        $this->mentor_name = $mentor_name;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Access to Ebook 🥳',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.ebook_purchase_success',
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
