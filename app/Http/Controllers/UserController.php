<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Solo usuarios admin pueden listar
        $user = $request->user();

        if ($user->role_id !== 1) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $users = User::all();
        return response()->json($users);
    }
}
