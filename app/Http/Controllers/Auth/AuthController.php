<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(
                Auth::user()->isAdmin() ? 'admin.dashboard' : 'empresa.dashboard'
            );
        }

        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        return redirect()->route(
            $user->isAdmin() ? 'admin.dashboard' : 'empresa.dashboard'
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('site.home');
    }

    public function cadastroEmpresa(): View
    {
        return view('auth.cadastro-empresa');
    }

    public function cadastroUsuario(Request $request): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->empresa_id) {
            return redirect()->route('empresa.usuarios.create');
        }

        return view('auth.cadastro-usuario');
    }

    public function esqueciSenha(): View
    {
        return view('auth.esqueci-senha');
    }

    public function redefinirSenha(): View
    {
        return view('auth.redefinir-senha');
    }
}
