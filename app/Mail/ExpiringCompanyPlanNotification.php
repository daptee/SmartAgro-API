<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiringCompanyPlanNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $plans;

    public function __construct($plans)
    {
        $this->plans = $plans;
    }

    public function envelope(): Envelope
    {
        if (config('services.app_environment') == 'DEV') {
            return new Envelope(
                subject: 'Planes empresa próximos a vencer (30 días) - DEV',
            );
        } else {
            return new Envelope(
                subject: 'Planes empresa próximos a vencer (30 días)',
            );
        }
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expiring_company_plan_notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
