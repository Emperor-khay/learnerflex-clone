<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MentorshipPurchaseSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user_name;
    public $mentor_name;
    public $product_access_link;
    public $aff_id;

    public function __construct($user_name, $mentor_name, $product_access_link, $aff_id)
    {
        $this->user_name = $user_name;
        $this->mentor_name = $mentor_name;
        $this->product_access_link = $product_access_link;
        $this->aff_id = $aff_id;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mentorship Purchase Success Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.mentorship_purchase_success',
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
