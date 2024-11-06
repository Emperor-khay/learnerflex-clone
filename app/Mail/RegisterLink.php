<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegisterLink extends Mailable
{
    use Queueable, SerializesModels;

    public $aff_id;

    /**
     * Create a new message instance.
     */
    public function __construct($aff_id)
    {
        $this->aff_id = $aff_id;
    }

    public function build()
    {
        $url = "https://learnerflex.com/auth/signup?aff_id=" . $this->aff_id;

        return $this->markdown('mail.register-link')
                    ->with([
                        'aff_id' => $this->aff_id,
                        'url' => $url,
                    ])
                    ->subject('Product Purchase Successful - Register with Link');
    }

    public function attachments(): array
    {
        return [];
    }
}
