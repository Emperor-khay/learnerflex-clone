<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorSaleNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $product_name;
    public $transaction_amount;
    public $email;
    public $reference;

    /**
     * Create a new message instance.
     */
    public function __construct($product_name, $transaction_amount, $email,$reference)
    {
        $this->product_name = $product_name;
        $this->transaction_amount = $transaction_amount;
        $this->email = $email;
        $this->reference = $reference;
    }

   

    public function build()
    {
        return $this->subject('New Product Sale')
                    ->view('mail.vendor_sale_notification');
    }
   
}
