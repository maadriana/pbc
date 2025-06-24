<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends BaseController
{
    protected $authService;

    public function __construct(AuthService $authService = null)
    {
        $this->authService = $authService ?? app(AuthService::class);
    }

    public function login(Request $request)
    {
        try {
            // Basic validation
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'remember' => 'boolean'
            ]);

            $result = $this->authService->login($credentials);

            if (!$result['success']) {
                // For web requests, redirect back with errors
                if (!$request->expectsJson()) {
                    return redirect()->back()
                        ->withErrors(['email' => $result['message']])
                        ->withInput($request->only('email', 'remember'));
                }

                return $this->error($result['message'], null, 401);
            }

            // For web requests, redirect to dashboard
            if (!$request->expectsJson()) {
                return redirect()->intended(route('dashboard'));
            }

            return $this->success($result['data'], 'Login successful');
        } catch (\Exception $e) {
            \Log::error('Login failed: ' . $e->getMessage());

            // For web requests, redirect back with errors
            if (!$request->expectsJson()) {
                return redirect()->back()
                    ->withErrors(['email' => 'Login failed. Please try again.'])
                    ->withInput($request->only('email', 'remember'));
            }

            return $this->error('Login failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $userData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|in:system_admin,engagement_partner,manager,associate,guest',
                'entity' => 'nullable|string',
                'access_level' => 'required|integer|between:1,5'
            ]);

            $result = $this->authService->register($userData);

            // For web requests, redirect to dashboard
            if (!$request->expectsJson()) {
                return redirect()->route('dashboard')->with('success', 'Registration successful!');
            }

            return $this->success($result, 'User registered successfully', 201);
        } catch (\Exception $e) {
            \Log::error('Registration failed: ' . $e->getMessage());

            // For web requests, redirect back with errors
            if (!$request->expectsJson()) {
                return redirect()->back()
                    ->withErrors(['email' => 'Registration failed. Please try again.'])
                    ->withInput();
            }

            return $this->error('Registration failed: ' . $e->getMessage(), null, 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // For web requests, redirect to login
            if (!$request->expectsJson()) {
                return redirect()->route('login')->with('status', 'You have been logged out successfully.');
            }

            return $this->success(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->error('Logout failed', $e->getMessage(), 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->error('User not authenticated', null, 401);
            }

            $user->load(['permissions']);
            return $this->success($user, 'User profile retrieved');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve user profile', $e->getMessage(), 500);
        }
    }
}
