<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
            'address'    => $request->address,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => 'customer',
            'status'     => 'active'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration completed successfully',
            'data' => [
                'user' => [
                    'id'                => $user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'full_name'         => $user->full_name,
                    'phone'             => $user->phone,
                    'address'           => $user->address,
                    'email'             => $user->email,
                    'role'              => $user->role,
                    'status'            => $user->status,
                    'is_profile_complete' => $user->isProfileComplete(),
                    'is_active'         => $user->isActive(),
                    'is_admin'          => $user->isAdmin(),
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ],
            'redirect_to' => '/customer/home'
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email address is not registered'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 401);
        }

        if (!$user->isActive()) {
            $statusMessages = [
                'inactive' => 'Account is inactive. Please contact support',
                'banned'   => 'This account has been banned'
            ];

            return response()->json([
                'success' => false,
                'message' => $statusMessages[$user->status] ?? 'Account is not available',
                'account_status' => $user->status
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        $redirectTo = $user->isAdmin() ? '/admin/dashboard' : '/customer/home';

        return response()->json([
            'success' => true,
            'message' => 'Login completed successfully',
            'data' => [
                'user' => [
                    'id'                => $user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'full_name'         => $user->full_name,
                    'phone'             => $user->phone,
                    'address'           => $user->address,
                    'email'             => $user->email,
                    'role'              => $user->role,
                    'status'            => $user->status,
                    'is_profile_complete' => $user->isProfileComplete(),
                    'is_active'         => $user->isActive(),
                    'is_admin'          => $user->isAdmin(),
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ],
            'redirect_to' => $redirectTo
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id'                => $user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'full_name'         => $user->full_name,
                    'phone'             => $user->phone,
                    'address'           => $user->address,
                    'email'             => $user->email,
                    'role'              => $user->role,
                    'status'            => $user->status,
                    'is_profile_complete' => $user->isProfileComplete(),
                    'is_active'         => $user->isActive(),
                    'is_admin'          => $user->isAdmin(),
                ]
            ]
        ]);
    }
}
