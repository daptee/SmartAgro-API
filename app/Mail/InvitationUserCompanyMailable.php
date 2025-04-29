<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationUserCompanyMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $company;
    public $data;
    /**
     * Create a new message instance.
     */
    public function __construct($user, $company, $data)
    {
        $this->user = (object) $user;
        $this->company = (object) $company;
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        if (config('services.app_environment') == 'DEV') {
            return new Envelope(
                subject: 'Bienvenido a SmartAgro - DEV',
            );
        } else {
            return new Envelope(
                subject: 'Bienvenido a SmartAgro',
            );
        }
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation_user_company',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
