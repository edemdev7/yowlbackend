<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $verificationUrl;

    public function __construct($verificationUrl)
    {
        $this->verificationUrl = $verificationUrl;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Please Verify Your Email Address',
            replyTo: [config('mail.from.address')],
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.verify',
            with: ['verificationUrl' => $this->verificationUrl],
        );
    }
}
