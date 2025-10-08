<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Models\Role;

// Controlador para manejar el registro, inicio de sesión y cierre de sesión
class AuthController extends Controller
{
    //Función registrar
    public function register(Request $request)
    {
        //Valida que los campos sean correctos
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8'
        ]);

        $customerRole = Role::where('label', 'customer')->first();

        //Crea el usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $customerRole->id // Siempre asigna 'customer' por defecto, otros roles los asigna el admin
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        Http::post('http://127.0.0.1:8003/api/notifications/', [
            'user_id' => $user->id,
            'title' => 'Registro exitoso',
            'message' => 'Tu cuenta ha sido creada exitosamente. ¡Bienvenido a ConcesionarioApp!',
            'type' => 'info'
        ]);

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    //Iniciar sesión
    public function login(Request $request)
    {
        //Validar datos
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message'=> 'credenciales invalidas'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user_name' => $user->name,
            'token_type' => 'Bearer'
        ]);
    }

    //Cerrar sesión
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    //Obtener información del usuario autenticado
   public function me(Request $request)
    {
        $user = $request->user()->load('role'); // Relación definida en User.php

        return response()->json([
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'role_id' => $user->role_id,
            'role'    => $user->role->name,
        ]);
    }
}
