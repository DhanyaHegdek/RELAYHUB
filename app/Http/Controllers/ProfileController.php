<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    //GET /api/profile Return the authenticated user's full profile
     
    public function show()
    {
        $user = Auth::guard('api')->user();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'bio'   => $user->bio,
            'role'  => $user->roles->first()?->name ?? 'user',
        ]);
    }

    //PUT /api/profile Update name, bio, email, and/or password
     
    public function update(Request $request)
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'bio'           => 'nullable|string|max:500',
            'email'         => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password'      => 'nullable|min:6|confirmed',
        ]);

        // Update fields if provided
        if ($request->has('name'))  $user->name  = $request->name;
        if ($request->has('bio'))   $user->bio   = $request->bio;
        if ($request->has('email')) $user->email = $request->email;

        // Only update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'bio'   => $user->bio,
            'role'  => $user->roles->first()?->name ?? 'user',
        ]);
    }
}