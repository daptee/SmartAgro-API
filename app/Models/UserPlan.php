<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user',
        'id_plan',
        'data',
        'preapproval_id',
        'next_payment_date',
    ];

    protected $table = "users_plans";

    /**
     * Relación con el modelo User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public static function save_history($id_user, $id_plan, $data, $next_payment_date, $preapproval_id)
    {
        Log::debug(["message" => "Guardando historial de planes de usuario", "id_user" => $id_user, "id_plan" => $id_plan, "data" => $data, "next_payment_date" => $next_payment_date, "preapproval_id" => $preapproval_id]);
        try {
            DB::beginTransaction();
                $user_plan = new UserPlan();
                $user_plan->id_user = $id_user;
                $user_plan->id_plan = $id_plan;
                $user_plan->data = json_encode($data);
                $user_plan->next_payment_date = $next_payment_date;
                $user_plan->preapproval_id = $preapproval_id;
                $user_plan->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => "Error al guardar historial planes de usuario", "error" => $e->getMessage(), "line" => $e->getLine()]);
        }
    }
}
