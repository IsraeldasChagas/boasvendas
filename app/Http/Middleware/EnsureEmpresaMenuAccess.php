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

        if (str_starts_with($routeName, 'empresa.venda-externa.dashboard')) {
            return 've_dashboard';
        }
        if (str_starts_with($routeName, 'empresa.venda-externa.pontos')) {
            return 've_pontos';
        }
        if (str_starts_with($routeName, 'empresa.venda-externa.remessas')) {
            return 've_remessas';
        }
        if (str_starts_with($routeName, 'empresa.venda-externa.acertos')) {
            return 've_acertos';
        }
        if (str_starts_with($routeName, 'empresa.venda-externa.fiados')) {
            return 've_fiados';
        }
        if (str_starts_with($routeName, 'empresa.venda-externa.relatorios')) {
            return 've_relatorios';
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
            if (str_starts_with($routeName, 'empresa.fidelidade.programa')) {
                return 'fidelidade_programa';
            }
            if (str_starts_with($routeName, 'empresa.fidelidade.cartoes')) {
                return 'fidelidade_cartoes';
            }

            return 'fidelidade_programa';
        }
        if (str_starts_with($routeName, 'empresa.entregas.')) {
            return 'entregas';
        }
        if (str_starts_with($routeName, 'empresa.financeiro.')) {
            if (str_starts_with($routeName, 'empresa.financeiro.despesas-fixas')) {
                return 'financeiro_despesas_fixas';
            }
            if (str_starts_with($routeName, 'empresa.financeiro.contas-receber')) {
                return 'financeiro_receber';
            }
            if (str_starts_with($routeName, 'empresa.financeiro.contas-pagar')) {
                return 'financeiro_pagar';
            }

            return 'financeiro_visao';
        }
        if (str_starts_with($routeName, 'empresa.caixa.')) {
            if (str_starts_with($routeName, 'empresa.caixa.conferencia')) {
                return 'caixa_conferencia';
            }
            if (str_starts_with($routeName, 'empresa.caixa.fluxo-diario')) {
                return 'caixa_fluxo_diario';
            }
            if (str_starts_with($routeName, 'empresa.caixa.movimento')
                || str_starts_with($routeName, 'empresa.caixa.abrir')
                || str_starts_with($routeName, 'empresa.caixa.fechar')) {
                return 'caixa_operacoes';
            }

            return 'caixa_visao';
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
