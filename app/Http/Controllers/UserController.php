<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\UserProfile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public $model = User::class;
    public $s = "usuario";
    public $sp = "usuarios";
    public $ss = "usuario/s";
    public $v = "o"; 
    public $pr = "el"; 
    public $prp = "los";

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function get_user_profile(Request $request)
    {
        $message = "Error al obtener registro";
        $action = "Perfil de usuario";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = User::getAllDataUser($id_user);
            Audith::new($id_user, $action, $request->all(), 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $id = Auth::user()->id;
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id),
            ],
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al actualizar usuario";
        $action = "Actualización de usuario";

        try {
            DB::beginTransaction();
                $user = User::find($id);
                $user->update($request->all());
              
                Audith::new($id, $action, $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($id, $action, $request->all(), 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = User::getAllDataUser($id);
        $message = "Usuario actualizado con exito";
        return response(compact("message", "data"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        $message = "Error al eliminar el usuario";
        $action = "Eliminación de usuario";
        $id_user = Auth::user()->id;
    
        try {
            DB::beginTransaction();
            
            $user = User::find($id_user);
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado',
                ], 404);
            }
    
            $user->delete();
    
            Audith::new($id_user, $action, ['deleted_user_id' => $id_user], 200, null);
            
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Audith::new($id_user, $action, ['deleted_user_id' => $id_user], 500, $e->getMessage());
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            return response()->json([
                'message' => $message,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    
        $message = "Usuario eliminado con éxito";
        return response()->json(compact("message"));
    }

    public function users_profiles()
    {
        $message = "Error al obtener registros";
        $action = "Listado de perfiles de usuario";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            $data = UserProfile::orderBy('name')->get();
            Audith::new($id_user, $action, null, 200, null);
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $message = $action;
        return response(compact("message", "data"));
    }

    public function change_status(Request $request, $id)
    {
        $request->validate([
            'id_status' => 'required|numeric|exists:users_status,id'
        ]);

        $message = "Error al actualizar estado de usuario";
        $action = "Actualización de estado de usuario";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            DB::beginTransaction();

            $user = $this->model::find($id);

            if(!$user)
                return response()->json(['message' => 'Usuario no valido.'], 400);

            $user->id_status = $request->id_status;
            $user->save();

            Audith::new($id_user, $action, null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = $this->model::getAllDataUser($id);
        $message = "Actualización de estado de usuario exitosa.";
        return response(compact("message", "data"));
    }

    public function change_plan(Request $request, $id)
    {
        $request->validate([
            'id_plan' => 'required|numeric|exists:plans,id'
        ]);

        $message = "Error al actualizar plan de usuario";
        $action = "Actualización de plan de usuario";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            DB::beginTransaction();
            $user = $this->model::find($id);

            if(!$user)
                return response()->json(['message' => 'Usuario no valido.'], 400);

            $user->id_plan = $request->id_plan;
            $user->save();

            UserPlan::save_history($user->id, $request->id_plan, "2024-08-21", "2024-08-31");

            Audith::new($id_user, $action, null, 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, null, 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = $this->model::getAllDataUser($id);
        $message = "Actualización de plan de usuario exitosa.";
        return response(compact("message", "data"));
    }

    public function profile_picture(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ], 422);
        }

        $message = "Error al actualizar imagen de perfil";
        $action = "Actualización de imagen de perfil";
        $data = null;
        $id_user = Auth::user()->id ?? null;
        try {
            DB::beginTransaction();
            if($request->id_user){
                $user = $this->model::find($request->id_user);
                if(!$user){
                    return response(["message" => "Usuario invalido"], 400);
                }
            }else{
                $user = Auth::user();
            }

            if($user->profile_picture){
                $file_path = public_path($user->profile_picture);
            
                if (file_exists($file_path))
                    unlink($file_path);
            }

            $path = $this->save_image_public_folder($request->profile_picture, "users/profiles/", null);
            
            $user->profile_picture = $path;
            $user->save();

            Audith::new($id_user, $action, $request->all(), 200, null);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        $data = $this->model::getAllDataUser($user->id);
        $message = "Actualización de imagen de perfil exitosa.";
        return response(compact("message", "data"));
    }

    public function save_image_public_folder($file, $path_to_save, $variable_id)
    {
        $fileName = Str::random(5) . time() . '.' . $file->extension();
                        
        if($variable_id){
            $file->move(public_path($path_to_save . $variable_id), $fileName);
            $path = "/" . $path_to_save . $variable_id . "/$fileName";
        }else{
            $file->move(public_path($path_to_save), $fileName);
            $path = "/" . $path_to_save . $fileName;
        }

        return $path;
    }
}
