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
    public $vendor_name;
    public $affiliate_name;

    /**
     * Create a new message instance.
     */
    public function __construct($product_name, $transaction_amount, $email,$reference, $vendor_name, $affiliate_name)
    {
        $this->product_name = $product_name;
        $this->transaction_amount = $transaction_amount;
        $this->email = $email;
        $this->reference = $reference;
        $this->vendor_name = $vendor_name;
        $this->affiliate_name = $affiliate_name;
    }

   

    public function build()
    {
        return $this->subject('Money Alert, ' . $this->vendor_name)
                    ->view('mail.vendor_sale_notification');
    }
   
}
