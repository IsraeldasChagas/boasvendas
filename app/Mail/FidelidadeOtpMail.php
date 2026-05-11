<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FidelidadeOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $corpoTexto,
        public string $nomeLoja,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código fidelidade — '.$this->nomeLoja,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<pre style="font-family:system-ui,sans-serif;white-space:pre-wrap;">'.e($this->corpoTexto).'</pre>',
        );
    }
}
