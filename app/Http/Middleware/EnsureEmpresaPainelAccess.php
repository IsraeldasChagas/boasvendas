<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o painel /empresa só é usado no contexto multi-empresa válido:
 * utilizador com empresa ativa (não suspensa). Master sem empresa vai para /admin.
 */
class EnsureEmpresaPainelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isAdmin() && ! $user->empresa_id) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->empresa_id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Esta conta não está vinculada a uma empresa.']);
        }

        $empresa = $user->empresa;
        if (! $empresa) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Empresa inválida ou removida.']);
        }

        if ($empresa->status === 'suspensa') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('site.home')
                ->with('warning', 'O acesso ao painel está indisponível: empresa suspensa.');
        }

        return $next($request);
    }
}
