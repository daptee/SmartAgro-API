<?php

// app/Models/PaymentHistory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $table = 'payment_history';
    protected $fillable = [
        'id_user',
        'type',
        'data',
        'preapproval_id',
        'payment_id',
        'amount',
        'subscription_type',
        'error_message'
    ];

    /**
     * La columna `data` es JSON: si llega un array (ej. respuesta de MercadoPago) se codifica,
     * si no el insert falla con "Invalid JSON text". La lectura sigue devolviendo el string.
     */
    public function setDataAttribute($value): void
    {
        $this->attributes['data'] = is_array($value) || is_object($value) ? json_encode($value) : $value;
    }
}

