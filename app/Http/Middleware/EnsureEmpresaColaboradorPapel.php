<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limita rotas do painel /empresa para perfis operacionais (atendente / atendente caixa).
 * Gestor, operador e entregador seguem sem esta restrição.
 */
class EnsureEmpresaColaboradorPapel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        $nome = (string) ($request->route()?->getName() ?? '');
        if ($nome === '' || ! str_starts_with($nome, 'empresa.')) {
            return $next($request);
        }

        if (! $user->temAcessoRestritoAoPainelEmpresa()) {
            return $next($request);
        }

        if (! $user->podeAcessarRotaEmpresa($nome)) {
            abort(403, 'Seu perfil não tem acesso a esta área do painel.');
        }

        return $next($request);
    }
}
