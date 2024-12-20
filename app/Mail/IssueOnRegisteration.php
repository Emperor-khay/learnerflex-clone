<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssueOnRegisteration extends Mailable
{
    use Queueable, SerializesModels;

    public $orderID;
    public $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($orderID, $email)
    {
        $this->orderID = $orderID;
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Issue with User Registration')
                    ->view('mail.issue_on_registration');
    }
}