<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    // GET - Pronóstico semanal por ubicación (lat/lng)
    public function weekly_forecast(Request $request)
    {
        $message = "Error al obtener el pronóstico del clima";
        $action = "Pronóstico semanal del clima";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
            ]);

            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'daily' => 'weathercode,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,windspeed_10m_max',
                'timezone' => 'auto',
                'forecast_days' => 7,
            ]);

            if (!$response->successful()) {
                Audith::new($id_user, $action, $request->all(), 500, $response->json());
                return response(["message" => $message, "error" => $response->json()], 500);
            }

            $forecast = $response->json();
            $daily = $forecast['daily'] ?? [];

            $data = collect($daily['time'] ?? [])->map(function ($date, $i) use ($daily) {
                return [
                    'date' => $date,
                    'weather_code' => $daily['weathercode'][$i] ?? null,
                    'temp_max' => $daily['temperature_2m_max'][$i] ?? null,
                    'temp_min' => $daily['temperature_2m_min'][$i] ?? null,
                    'precipitation_sum' => $daily['precipitation_sum'][$i] ?? null,
                    'precipitation_probability_max' => $daily['precipitation_probability_max'][$i] ?? null,
                    'windspeed_max' => $daily['windspeed_10m_max'][$i] ?? null,
                ];
            })->values();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }

    // GET - Clima hora a hora de las últimas 24hs del día de hoy, por ubicación (lat/lng)
    public function hourly_today(Request $request)
    {
        $message = "Error al obtener el clima de hoy";
        $action = "Clima hora a hora de hoy";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
            ]);

            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'hourly' => 'temperature_2m,precipitation,precipitation_probability,weathercode,windspeed_10m,relativehumidity_2m',
                'timezone' => 'auto',
                'forecast_days' => 1,
            ]);

            if (!$response->successful()) {
                Audith::new($id_user, $action, $request->all(), 500, $response->json());
                return response(["message" => $message, "error" => $response->json()], 500);
            }

            $forecast = $response->json();
            $hourly = $forecast['hourly'] ?? [];

            $data = collect($hourly['time'] ?? [])->map(function ($time, $i) use ($hourly) {
                return [
                    'time' => $time,
                    'weather_code' => $hourly['weathercode'][$i] ?? null,
                    'temperature' => $hourly['temperature_2m'][$i] ?? null,
                    'humidity' => $hourly['relativehumidity_2m'][$i] ?? null,
                    'precipitation' => $hourly['precipitation'][$i] ?? null,
                    'precipitation_probability' => $hourly['precipitation_probability'][$i] ?? null,
                    'windspeed' => $hourly['windspeed_10m'][$i] ?? null,
                ];
            })->values();

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
