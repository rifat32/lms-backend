<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // POST /api/auth/register
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|string|in:student,lecturer,admin',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign role via Spatie
        $user->assignRole($request->role);

        // Generate Passport token
        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'user_id' => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'token'   => $token,
        ], 201);
    }

    // POST /api/auth/login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'user_id' => $user->id,
            'role'    => $user->roles->pluck('name')->first(),
            'token'   => $token,
        ]);
    }
}
