<?php

namespace App\Http\Controllers;

use App\Models\Audith;
use App\Models\Locality;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    // GET - Clima hora a hora de una fecha puntual, por ubicación (lat/lng)
    public function hourly_by_date(Request $request)
    {
        $message = "Error al obtener el clima de la fecha indicada";
        $action = "Clima hora a hora por fecha";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'lat' => 'required|numeric|between:-90,90',
                'lng' => 'required|numeric|between:-180,180',
                'date' => 'required|date',
            ]);

            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'hourly' => 'temperature_2m,precipitation,precipitation_probability,weathercode,windspeed_10m,relativehumidity_2m',
                'timezone' => 'auto',
                'start_date' => $request->date,
                'end_date' => $request->date,
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

    // GET - Pronóstico semanal a partir de una localidad (locality_id) de la base
    public function by_locality(Request $request)
    {
        $message = "Error al obtener el pronóstico del clima para la localidad";
        $action = "Pronóstico semanal del clima por localidad";
        $data = null;
        $id_user = Auth::user()->id ?? null;

        try {
            $request->validate([
                'locality_id' => 'required|integer|exists:localities,id',
            ]);

            $locality = Locality::with('province')->findOrFail($request->locality_id);

            $geoResponse = Http::get('https://geocoding-api.open-meteo.com/v1/search', [
                'name' => $locality->name,
                'count' => 10,
                'language' => 'es',
                'format' => 'json',
                'country' => 'AR',
            ]);

            if (!$geoResponse->successful()) {
                Audith::new($id_user, $action, $request->all(), 500, $geoResponse->json());
                return response(["message" => "Error al ubicar la localidad", "error" => $geoResponse->json()], 500);
            }

            $results = $geoResponse->json()['results'] ?? [];

            if (empty($results)) {
                Audith::new($id_user, $action, $request->all(), 404, null);
                return response(["message" => "No se pudo ubicar la localidad '{$locality->name}'"], 404);
            }

            $provinceName = $locality->province->name ?? null;

            $match = collect($results)->first(function ($result) use ($provinceName) {
                return $provinceName && isset($result['admin1'])
                    && Str::contains(Str::lower($result['admin1']), Str::lower($provinceName));
            }) ?? $results[0];

            $response = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $match['latitude'],
                'longitude' => $match['longitude'],
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

            $data = [
                'locality' => $locality->name,
                'province' => $provinceName,
                'lat' => $match['latitude'],
                'lng' => $match['longitude'],
                'forecast' => collect($daily['time'] ?? [])->map(function ($date, $i) use ($daily) {
                    return [
                        'date' => $date,
                        'weather_code' => $daily['weathercode'][$i] ?? null,
                        'temp_max' => $daily['temperature_2m_max'][$i] ?? null,
                        'temp_min' => $daily['temperature_2m_min'][$i] ?? null,
                        'precipitation_sum' => $daily['precipitation_sum'][$i] ?? null,
                        'precipitation_probability_max' => $daily['precipitation_probability_max'][$i] ?? null,
                        'windspeed_max' => $daily['windspeed_10m_max'][$i] ?? null,
                    ];
                })->values(),
            ];

            Audith::new($id_user, $action, $request->all(), 200, compact("data"));
        } catch (Exception $e) {
            Log::debug(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()]);
            Audith::new($id_user, $action, $request->all(), 500, $e->getMessage());
            return response(["message" => $message, "error" => $e->getMessage(), "line" => $e->getLine()], 500);
        }

        return response(compact("data"));
    }
}
