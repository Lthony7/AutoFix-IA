<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Src\Auth\Application\Controllers\UsuarioWebController;
use Src\Auth\Application\Controllers\WebAuthController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);

    Route::get('/register', [WebAuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::resource('usuarios', UsuarioWebController::class)->except(['show']);
});
