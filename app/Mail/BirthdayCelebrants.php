<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BirthdayCelebrants extends Mailable
{
    use Queueable, SerializesModels;

    public $celebrants;

    public function __construct(Collection $celebrants)
    {
        $this->celebrants = $celebrants;
    }

    public function build()
    {
        return $this->subject('🎉 Aniversariantes - Núcleo Desenvolve 🎉')
                    ->view('emails.celebrants');
    }
}
