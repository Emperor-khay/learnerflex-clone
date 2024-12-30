<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DigitalProductPurchaseSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user_name;
    public $product_name;
    public $email;
    public $product_access_link;
    public $aff_id;

    public function __construct($user_name, $product_name, $email, $product_access_link, $aff_id)
    {
        $this->user_name = $user_name;
        $this->product_name = $product_name;
        $this->email = $email;
        $this->product_access_link = $product_access_link;
        $this->aff_id = $aff_id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Access product and free account 🥳',
        );
    }
    public function content(): Content
    {
        return new Content(
            view: 'mail.digital_product_purchase_success',
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
