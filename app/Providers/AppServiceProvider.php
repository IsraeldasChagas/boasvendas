<?php

namespace App\Providers;

use App\Models\Adicional;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Mesa;
use App\Models\FidelidadeCartao;
use App\Models\FinanceiroDespesaFixa;
use App\Models\FinanceiroTitulo;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\SuporteTicket;
use App\Models\User;
use App\Models\VeAcerto;
use App\Models\VeFiado;
use App\Models\VePonto;
use App\Models\VeRemessa;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();

        Route::bind('adicional', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return Adicional::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('produto', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return Produto::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('categoria', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return Categoria::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('cliente', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return Cliente::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('fidelidadeCartao', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return FidelidadeCartao::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('financeiroTitulo', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return FinanceiroTitulo::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('financeiroDespesaFixa', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return FinanceiroDespesaFixa::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('vePonto', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return VePonto::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('veRemessa', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return VeRemessa::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('veAcerto', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return VeAcerto::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('veFiado', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return VeFiado::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('suporteTicket', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return SuporteTicket::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('usuario', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return User::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('pedido', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return Pedido::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('mesa', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return Mesa::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('comanda', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            return Comanda::query()
                ->where('id', $value)
                ->where('empresa_id', $empresaId)
                ->firstOrFail();
        });

        Route::bind('comandaItem', function (string $value) {
            if (! auth()->check()) {
                abort(404);
            }

            $empresaId = auth()->user()->empresa_id;
            abort_unless($empresaId, 404);

            $item = ComandaItem::query()
                ->where('id', $value)
                ->whereHas('comanda', fn ($q) => $q->where('empresa_id', $empresaId))
                ->firstOrFail();

            return $item;
        });

        View::composer('layouts.publico', function ($view) {
            $slug = request()->route('slug');
            if (! is_string($slug) || $slug === '') {
                $view->with('carrinhoContagem', 0);
                $view->with('vfPassoCompra', null);
                $view->with('vfPedidoShowUrl', null);
                $view->with('vfRodapeFluirCompra', false);
                $view->with('vfOcultarNavPublica', false);

                return;
            }

            $raw = session('loja_carrinho.'.$slug, []);
            $count = 0;
            if (is_array($raw) && $raw !== []) {
                if (isset($raw[0]) && is_array($raw[0])) {
                    $first = $raw[0];
                    if (array_key_exists('produto_id', $first)
                        || array_key_exists('adicional_avulso_id', $first)
                        || (($first['linha_tipo'] ?? '') === 'adicional_avulso')) {
                        foreach ($raw as $line) {
                            if (is_array($line)) {
                                $count += (int) ($line['quantidade'] ?? 0);
                            }
                        }
                    }
                } else {
                    foreach ($raw as $qty) {
                        if (is_numeric($qty)) {
                            $count += (int) $qty;
                        }
                    }
                }
            }

            $nomeRota = Route::currentRouteName();
            $passosPorRota = [
                'publico.loja' => 'loja',
                'publico.produto' => 'loja',
                'publico.fidelidade' => 'loja',
                'publico.fidelidade.solicitar-codigo' => 'loja',
                'publico.fidelidade.reenviar-codigo' => 'loja',
                'publico.fidelidade.cancelar-otp' => 'loja',
                'publico.fidelidade.verificar-codigo' => 'loja',
                'publico.fidelidade.cadastrar' => 'loja',
                'publico.carrinho' => 'carrinho',
                'publico.checkout' => 'checkout',
                'publico.pedido.show' => 'pedido',
                'publico.acompanhar' => 'loja',
                'publico.acompanhar.buscar' => 'loja',
            ];
            $passo = $passosPorRota[$nomeRota] ?? 'loja';

            // Página do entregador: mesmo rodapé “fluindo” que checkout/carrinho; sem barra de etapas da compra.
            if ($nomeRota === 'publico.entregador.show') {
                $passo = null;
            }

            $pedidoShowUrl = null;
            if ($nomeRota === 'publico.pedido.show') {
                $codigo = request()->route('codigo');
                if (is_string($codigo) && $codigo !== '') {
                    $pedidoShowUrl = route('publico.pedido.show', ['slug' => $slug, 'codigo' => $codigo]);
                }
            }

            $rotasRodapeNoFluxoCompra = [
                'publico.carrinho',
                'publico.checkout',
                'publico.pedido.show',
                'publico.acompanhar',
                'publico.acompanhar.buscar',
                'publico.entregador.show',
                // Página do produto: barra inferior / ações não ficam sob o rodapé fixo.
                'publico.produto',
                // Fidelidade na vitrine: mesma lógica do fluxo de compra (rodapé fixo cobria botões).
                'publico.fidelidade',
                'publico.fidelidade.solicitar-codigo',
                'publico.fidelidade.reenviar-codigo',
                'publico.fidelidade.cancelar-otp',
                'publico.fidelidade.sair',
                'publico.fidelidade.verificar-codigo',
                'publico.fidelidade.cadastrar',
            ];
            $rodapeFluirCompra = in_array($nomeRota, $rotasRodapeNoFluxoCompra, true);

            $view->with([
                'carrinhoContagem' => $count,
                'vfPassoCompra' => $passo,
                'vfPedidoShowUrl' => $pedidoShowUrl,
                'vfRodapeFluirCompra' => $rodapeFluirCompra,
                'vfOcultarNavPublica' => $nomeRota === 'publico.entregador.show',
            ]);
        });

        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            $user = Auth::user();
            if ($user && $user->acessaPainelMaster()) {
                return route('admin.dashboard');
            }

            return route('empresa.dashboard');
        });
    }
}
