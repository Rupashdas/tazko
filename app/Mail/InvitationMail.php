<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable {
    use Queueable, SerializesModels;

    public string $link;

    public function __construct(public Invitation $invitation) {
        $frontend = config('app.frontend_url') ?? config('app.url');
        $this->link = rtrim($frontend, '/') . '/accept-invitation?token=' . $invitation->token;
    }

    public function envelope(): Envelope {
        return new Envelope(
            subject: 'You\'ve been invited to join ' . config('app.name'),
        );
    }

    public function content(): Content {
        return new Content(
            view: 'emails.invitation',
            with: [
                'invitation' => $this->invitation,
                'link'       => $this->link,
                'appName'    => config('app.name'),
            ]
        );
    }

    public function attachments(): array {
        return [];
    }
}
