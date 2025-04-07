<?php

namespace App\Http\Controllers;

use App\Mail\ResearchOnDemandMailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Audith;
use App\Models\Country;
use App\Models\Province;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Auth;

class ResearchOnDemand extends Controller
{
    public function research_on_demand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'study_type' => 'required|string|max:255',
            'cases_count' => 'required|string|max:255',
            'investment_range' => 'required|string|max:255',
            'country_id' => 'required|integer|exists:countries,id',
            'province_ids' => 'required|array',
            'province_ids.*' => 'integer|exists:provinces,id',
            'description' => 'required|string',
            'g-recaptcha-response' => 'required'
        ]);

        $action = "Research on demand";
        $status = 422;
        $id_user = Auth::user()->id ?? null;

        if ($validator->fails()) {
            $response = [
                'message' => 'Alguna de las validaciones falló',
                'errors' => $validator->errors(),
            ];
            Audith::new($id_user, $action, $request->all(), $status, $response);
            return response()->json($response, $status);
        }

        $message = "Envio de mail 'research on demand' exitoso.";
        $data = $request->all();

        $params = [
            'secret' => config('services.recaptcha_secret_key'),
            'response' => $request->input('g-recaptcha-response')
        ];

        $url = 'https://www.google.com/recaptcha/api/siteverify?' . http_build_query($params);

        $response = file_get_contents($url);

        $responseData = json_decode($response, true);

        // Verifica si la puntuación es mayor o igual a 0.5
        if (isset($responseData['score']) && $responseData['score'] >= 0.5) {
            try {
                $data['country'] = Country::find($data['country_id']);
                $data['provinces'] = Province::whereIn('id', $data['province_ids'])->get();

                Mail::to(config('services.research_on_demand.email'))->send(new ResearchOnDemandMailable($data));
                Audith::new($id_user, "Envio de mail 'research on demand' exitoso.", $request->all(), 200, compact("message", "data"));
            } catch (Exception $e) {
                $response = ["message" => "Error al enviar mail 'research on demand'.", "error" => $e->getMessage(), "line" => $e->getLine()];
                Audith::new($id_user, $action, $request->all(), 500, $response);
                Log::debug($response);
                return response()->json($response, 500);
            }
        } else {
            $response = ['message' => 'Error en validacion de recaptcha.'];
            Audith::new(null, $action, $request->all(), $status, $response);
            return response()->json($response, $status);
        }

        return response(compact("message", "data"));
    }
}
