<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $roleLabel,
    ) {}

    public function build()
    {
        return $this->subject('Your MediCare Password Reset Code')
            ->view('emails.password-reset-code')
            ->with([
                'code' => $this->code,
                'roleLabel' => $this->roleLabel,
            ]);
    }
}
