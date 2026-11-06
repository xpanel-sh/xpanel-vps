<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $alertTitle, public string $alertMessage) {}

    public function build(): self
    {
        return $this->subject($this->alertTitle)->text('emails.account-alert');
    }
}
