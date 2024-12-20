<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SkippedEmails extends Mailable
{
    use Queueable, SerializesModels;

    public $skippedEmails;

    /**
     * Create a new message instance.
     *
     * @param array $skippedEmails
     */
    public function __construct(array $skippedEmails)
    {
        $this->skippedEmails = $skippedEmails;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Skipped Affiliate Registrations')
                    ->view('mail.skipped_emails')
                    ->with(['skippedEmails' => $this->skippedEmails]);
    }
}
