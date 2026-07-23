<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Alert $alert)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AutoChain Emma+] '.$this->alert->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<h2>AutoChain Emma+ — Alerte</h2>'
                .'<p><strong>'.$this->alert->title.'</strong></p>'
                .'<p>'.$this->alert->message.'</p>'
                .'<p>Véhicule : '.($this->alert->vehicle?->license_plate ?? 'N/A').'</p>',
        );
    }
}
