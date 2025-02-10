<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\MassNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SendMassEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $users, $message;

    public function __construct($users, $message)
    {
        $this->users = $users;
        $this->message = $message;
    }

    public function handle()
    {
        foreach ($this->users as $user) {
            try {
                Mail::to($user->email)->send(new MassNotification($user, $this->message));
                Log::info("Correo enviado a: {$user->email}");
                sleep(1); // Evita bloqueo
            } catch (\Exception $e) {
                Log::error("Error enviando correo a {$user->email}: " . $e->getMessage());
            }
        }
    }
}