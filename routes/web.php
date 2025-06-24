<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard or login
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('register', function () {
        return view('auth.register');
    })->name('register');

    // Add these POST routes
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
});

// Add logout route (needs to be outside guest middleware)
Route::post('logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes
Route::middleware(['auth', 'pbc.permission'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('users/list', [UserController::class, 'index'])->name('user.index')->middleware('can:view_user');


    // Future routes for other pages
    Route::get('users', function () {
        return view('users.index');
    })->name('users.index')->middleware('can:view_user');

    Route::get('clients', function () {
        return view('clients.index');
    })->name('clients.index')->middleware('can:view_client');

    Route::get('projects', function () {
        return view('projects.index');
    })->name('projects.index')->middleware('can:view_project');

    Route::get('pbc-requests', function () {
        return view('pbc-requests.index');
    })->name('pbc-requests.index')->middleware('can:view_pbc_request');
});
