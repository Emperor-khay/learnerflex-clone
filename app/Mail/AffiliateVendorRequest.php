<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateVendorRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $saleUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $saleUrl)
    {
        $this->user = $user;
        $this->saleUrl = $saleUrl;
    }

    public function build()
    {
        return $this->view('mail.notify_affiliate_of_vendor_request')
                    ->subject('Your Vendor Request Submission')
                    ->with([
                        'userName' => $this->user->name,
                        'saleUrl' => $this->saleUrl,
                    ]);
    }
}
