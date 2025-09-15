<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // GET /api/users/{id}
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'profile_image' => $user->profile_image,
            'role'          => $user->roles->pluck('name')->first(),
        ]);
    }

    // PUT /api/users/{id}
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->only(['name', 'email', 'profile_image']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'profile_image' => $user->profile_image,
            'role'          => $user->roles->pluck('name')->first(),
        ]);
    }
}