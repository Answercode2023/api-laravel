<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        return response()->json($this->authService->register($data), 201);
    }

    public function login(Request $request)
    {

        try {
            $data = $request->validate([
                'email'    => 'required|string|email',
                'password' => 'required|string',
            ]);
            //code...
            return response()->json($this->authService->login($data), 200);
        } catch (\Throwable $th) {
            
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
            //throw $th;
        }

    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }
}
