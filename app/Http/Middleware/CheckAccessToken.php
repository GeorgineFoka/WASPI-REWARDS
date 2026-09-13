<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAccessToken
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Laisser passer les requêtes pré-vol CORS
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $token = $request->header('Authorization') ?? $request->query('access_token');
        $validToken = config('app.api_access_token', 'waspi_secret_token_2026');

        if (!$token || str_replace('Bearer ', '', $token) !== $validToken) {
            return response()->json(['error' => 'Accès non autorisé : token invalide ou absent.'], 401);
        }

        return $next($request);
    }
}