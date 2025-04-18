<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

class Audith extends Model
{
    use HasFactory;

    protected $table = "audith";

    protected $fillable = [
        'id_user',
        'action',
        'data',
        'result',
        'response',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id_user');
    }

    protected function casts(): array
    {
        return [
            'data' => 'json',
            'response' => 'json'
        ];
    }

    public static function new($id_user, $action, $data_json, $status, $response)
    {
        $message = "Error al guardar auditoria";
        try {
            $audith = new Audith();
            $audith->id_user = $id_user;
            $audith->action = $action;
            $audith->data = $data_json;
            $audith->result = $status;
            $audith->response = $response;
            $audith->save();
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return ["message" => $message, "status" => 500];
        }
    }
}
