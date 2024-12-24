<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorAccountWanted extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $saleurl;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $saleurl
     */
    public function __construct(User $user, $saleurl)
    {
        $this->user = $user;
        $this->saleurl = $saleurl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Vendor Account Request')
                    ->view('mail.admin_notification_of_vendor_account_request'); // Use the HTML view
    }
}
