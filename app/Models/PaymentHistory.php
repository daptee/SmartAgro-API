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
        'error_message'
    ];
}

