<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FilterIP
{
   // Lista blanca o negra de IPs
    protected $allowedIps = [
        '127.0.0.1', // Locust en tu máquina
        '::1',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! in_array($request->ip(), $this->allowedIps)) {
            return response()->json([
                'message' => 'Acceso no autorizado desde esta IP'
            ], 403);
        }

        return $next($request);
    }
}
