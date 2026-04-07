<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Painel /empresa: só equipa com empresa válida.
 * Contas master (painel /admin) nunca usam estas rotas — redireciona para /admin para evitar
 * erro "sem empresa" mesmo com cache/.env incorretos.
 */
class EnsureEmpresaPainelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($this->deveUsarSoPainelMaster($user)) {
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

    /**
     * @param  User  $user
     */
    private function deveUsarSoPainelMaster(object $user): bool
    {
        if (method_exists($user, 'acessaPainelMaster') && $user->acessaPainelMaster()) {
            return true;
        }

        $email = strtolower(trim((string) ($user->email ?? '')));

        return in_array($email, [
            'master@vendaffacil.com.br',
            'admin@vendaffacil.com.br',
        ], true);
    }
}
