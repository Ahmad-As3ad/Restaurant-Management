<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'phone' => 'required|string|digits:10|starts_with:09|unique:users',
                'address' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'customer',
                'status' => 'active'
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->first_name . ' ' . $user->last_name,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'email' => $user->email,
                        'role' => $user->role,
                        'status' => $user->status,
                        'is_profile_complete' => $user->isProfileComplete(),
                        'is_active' => $user->status === 'active',
                        'is_admin' => $user->role === 'admin'
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'redirect_to' => '/customer/home'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not registered'
                ], 404);
            }

            if (!Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            if ($user->status !== 'active') {
                $message = '';
                if ($user->status === 'inactive') {
                    $message = 'Your account is inactive. Please contact support';
                } elseif ($user->status === 'banned') {
                    $message = 'Your account has been banned';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'account_status' => $user->status
                ], 403);
            }

            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            $redirectTo = $user->role === 'admin' ? '/admin/dashboard' : '/customer/home';

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->first_name . ' ' . $user->last_name,
                        'phone' => $user->phone,
                        'address' => $user->address,
                        'email' => $user->email,
                        'role' => $user->role,
                        'status' => $user->status,
                        'is_profile_complete' => $user->isProfileComplete(),
                        'is_active' => $user->status === 'active',
                        'is_admin' => $user->role === 'admin'
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ],
                'redirect_to' => $redirectTo
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during logout'
            ], 500);
        }
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'is_profile_complete' => $user->isProfileComplete(),
                    'is_active' => $user->status === 'active',
                    'is_admin' => $user->role === 'admin'
                ]
            ]
        ]);
    }
}
