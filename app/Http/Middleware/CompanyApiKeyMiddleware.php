<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;

class CompanyApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json(['message' => 'API Key missing'], 401);
        }

        $company = Company::where('api_key', $apiKey)->first();

        if (!$company) {
            return response()->json(['message' => 'Invalid API Key'], 401);
        }

        // Verificar permisos (opcional, lo puedes usar en los controladores)
        $request->merge(['_company' => $company]);

        return $next($request);
    }
}
