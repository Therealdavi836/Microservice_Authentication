<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Models\Role;

class AuthController extends Controller
{
    protected $services;

    public function __construct()
    {
        $this->services = [
            'notifications' => env('NOTIFICATIONS_URL', 'http://127.0.0.1:8003/api/notifications'),
            'catalog'       => env('CATALOG_URL', 'http://127.0.0.1:8001/vehicles'),
            'publications'  => env('PUBLICATIONS_URL', 'http://127.0.0.1:8002'),
            'reports'       => env('REPORTS_URL', 'http://127.0.0.1:5000'),
            'sales'         => env('SALES_URL','http://127.0.0.1:8002')
        ];
    }

    // =====================================================
    // REGISTRO DE USUARIO
    // =====================================================
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8'
        ]);

        $customerRole = Role::where('label', 'customer')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $customerRole->id
        ]);

        // Enviar notificación directamente al microservicio
        $notificationData = [
            'user_id' => $user->id,
            'title' => 'Registro exitoso',
            'message' => 'Tu cuenta ha sido creada exitosamente. ¡Bienvenido a ConcesionarioApp!',
            'type' => 'info'
        ];

        Http::withHeaders([
            'Accept' => 'application/json'
        ])->post($this->services['notifications'], $notificationData);

        return response()->json(['message' => 'Usuario registrado exitosamente. Ahora puedes iniciar sesión.'], 201);
    }

    // =====================================================
    // INICIO DE SESIÓN
    // =====================================================
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message'=> 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user_name' => $user->name,
            'token_type' => 'Bearer'
        ]);
    }

    // =====================================================
    // CIERRE DE SESIÓN
    // =====================================================
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    // =====================================================
    // INFORMACIÓN DEL USUARIO AUTENTICADO
    // =====================================================
    public function me(Request $request)
    {
        $user = $request->user()->load('role');

        return response()->json([
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'role_id' => $user->role_id,
            'role'    => $user->role->name,
        ]);
    }

    // =====================================================
    // MÉTODO GATEWAY PARA REENVIAR PETICIONES
    // =====================================================
    public function forward(Request $request, $service, $endpoint = '')
    {
        if (!isset($this->services[$service])) {
            return response()->json(['error' => 'Servicio no válido'], 400);
        }

        $endpoint = $endpoint ?? '';
        $url = rtrim($this->services[$service], '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization' => $request->bearerToken(),
            'Accept' => 'application/json'
        ];

        switch ($request->method()) {
            case 'GET':
                $response = Http::withHeaders($headers)->get($url, $request->all());
                break;

            case 'POST':
                $response = Http::withHeaders($headers)->post($url, $request->json()->all());
                break;

            case 'PUT':
                $response = Http::withHeaders($headers)->put($url, $request->json()->all());
                break;

            case 'PATCH':
                $response = Http::withHeaders($headers)->patch($url, $request->json()->all());
                break;

            case 'DELETE':
                $response = Http::withHeaders($headers)->delete($url, $request->all());
                break;

            default:
                return response()->json(['error' => 'Método no permitido'], 405);
        }

        return $response->json();
    }
}
