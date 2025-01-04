<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateSaleNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $affiliate_name;
    public $product_name;
    public $commission;
    public $customer_name;
    public $customer_email;
    public $reference_id;

    public function __construct($affiliate_name, $product_name, $commission, $customer_name, $customer_email, $reference_id)
    {
        $this->affiliate_name = $affiliate_name;
        $this->product_name = $product_name;
        $this->commission = $commission;
        $this->customer_name = $customer_name;
        $this->customer_email = $customer_email;
        $this->reference_id = $reference_id;
    }
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Money Alert, ' . $this->affiliate_name . '💸 😃',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.affiliate_sale_notification',
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
