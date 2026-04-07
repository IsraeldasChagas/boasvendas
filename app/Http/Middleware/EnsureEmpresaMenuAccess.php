<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $empresa = $user?->empresa;

        if (! $user || ! $empresa) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if (! str_starts_with($routeName, 'empresa.')) {
            return $next($request);
        }

        $key = $this->mapRouteToMenuKey($routeName);
        if ($key === null) {
            return $next($request);
        }

        if (! $empresa->temTelaMenu($key)) {
            abort(403, 'Sua empresa não tem acesso a esta tela.');
        }

        return $next($request);
    }

    private function mapRouteToMenuKey(string $routeName): ?string
    {
        if ($routeName === 'empresa.dashboard') {
            return 'dashboard';
        }

        if (str_starts_with($routeName, 'empresa.venda-externa.')) {
            return 'venda_externa';
        }

        if (str_starts_with($routeName, 'empresa.pedidos.')) {
            return 'pedidos';
        }
        if (str_starts_with($routeName, 'empresa.produtos.')) {
            return 'produtos';
        }
        if (str_starts_with($routeName, 'empresa.categorias.')) {
            return 'categorias';
        }
        if (str_starts_with($routeName, 'empresa.adicionais.')) {
            return 'adicionais';
        }
        if (str_starts_with($routeName, 'empresa.clientes.')) {
            return 'clientes';
        }
        if (str_starts_with($routeName, 'empresa.fidelidade.')) {
            return 'fidelidade';
        }
        if (str_starts_with($routeName, 'empresa.entregas.')) {
            return 'entregas';
        }
        if (str_starts_with($routeName, 'empresa.financeiro.')) {
            return 'financeiro';
        }
        if (str_starts_with($routeName, 'empresa.caixa.')) {
            return 'caixa';
        }
        if (str_starts_with($routeName, 'empresa.relatorios.')) {
            return 'relatorios';
        }
        if (str_starts_with($routeName, 'empresa.chamados.')) {
            return 'suporte';
        }
        if (str_starts_with($routeName, 'empresa.configuracoes.')) {
            return 'configuracoes';
        }
        if (str_starts_with($routeName, 'empresa.usuarios.')) {
            return 'usuarios';
        }

        return null;
    }
}
