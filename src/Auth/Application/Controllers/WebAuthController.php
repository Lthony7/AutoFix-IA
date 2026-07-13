<?php

namespace Src\Auth\Application\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Src\Auth\Infrastructure\Models\UserEloquentModel;
use Src\Auth\Infrastructure\Requests\LoginRequest;
use Src\Auth\Infrastructure\Requests\RegisterRequest;

class WebAuthController extends Controller
{
    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function showRegisterForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return back()->withErrors([
                'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        if ($user && !$user->activo) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Tu usuario está inactivo. Contacta al administrador.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Bienvenido de vuelta.');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = UserEloquentModel::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Cliente,
            'activo' => true,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Registro exitoso. Bienvenido.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Sesión cerrada exitosamente.');
    }
}
