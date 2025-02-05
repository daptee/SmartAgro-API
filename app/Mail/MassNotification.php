<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MassNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user, $message;

    public function __construct($user, $message)
    {
        $this->user = $user;
        $this->message = $message;
    }

    public function build()
    {
        return $this->subject('Nuevo reporte en SmartAgro')
                    ->view('emails.notification_user')
                    ->with(['message' => $this->message, 'user' => $this->user]);
    }
}