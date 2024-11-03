<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $product_name;
    public $product_access_link;

    /**
     * Create a new message instance.
     */
    public function __construct($product_name, $product_access_link)
    {
        $this->product_name = $product_name;
        $this->product_access_link = $product_access_link;
    }

    public function build()
    {
        return $this->subject('Purchase Successful')
            ->view('Mail.buyer_purchase_success');
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
