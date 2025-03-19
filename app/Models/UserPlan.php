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
        'data'
    ];

    protected $table = "users_plans";

    public static function save_history($id_user, $id_plan, $data, $next_payment_date)
    {
        try {
            DB::beginTransaction();
                $user_plan = new UserPlan();
                $user_plan->id_user = $id_user;
                $user_plan->id_plan = $id_plan;
                $user_plan->data = json_encode($data);
                $user_plan->next_payment_date = $next_payment_date;
                $user_plan->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => "Error al guardar historial planes de usuario", "error" => $e->getMessage(), "line" => $e->getLine()]);
        }
    }
}
