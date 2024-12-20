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
        'start_date',
        'finish_date',
        'price'
    ];

    protected $table = "users_plans";

    public static function save_history($id_user, $id_plan, $start_date, $finish_date, $price = null)
    {
        try {
            DB::beginTransaction();
                $user_plan = new UserPlan();
                $user_plan->id_user = $id_user;
                $user_plan->id_plan = $id_plan;
                $user_plan->start_date = $start_date;
                $user_plan->finish_date = $finish_date;
                $user_plan->price = $price;
                $user_plan->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => "Error al guardar historial planes de usuario", "error" => $e->getMessage(), "line" => $e->getLine()]);
        }
    }
}
