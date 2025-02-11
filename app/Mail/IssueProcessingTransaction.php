<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IssueProcessingTransaction extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $orderId;
    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($email, $orderId, $customMessage)
    {
        $this->email = $email;
        $this->orderId = $orderId;
        $this->customMessage = $customMessage;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Issue Processing Transaction')
            ->mailer('admin_mailer') // Use custom mailer
            ->view('mail.issue_processing_transaction'); // Remove .blade.php

    }
}

