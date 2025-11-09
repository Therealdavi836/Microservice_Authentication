<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Compara con el label del rol del usuario
        if ($request->user()->role->label !== $role) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}
