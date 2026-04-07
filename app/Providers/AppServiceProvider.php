<?php

namespace App\Providers;

use App\Models\Adicional;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\FidelidadeCartao;
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

        View::composer('layouts.publico', function ($view) {
            $slug = request()->route('slug');
            if (! is_string($slug) || $slug === '') {
                $view->with('carrinhoContagem', 0);

                return;
            }
            $raw = session('loja_carrinho.'.$slug, []);
            $count = 0;
            if (is_array($raw) && $raw !== []) {
                if (isset($raw[0]) && is_array($raw[0]) && array_key_exists('produto_id', $raw[0])) {
                    foreach ($raw as $line) {
                        if (is_array($line)) {
                            $count += (int) ($line['quantidade'] ?? 0);
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
            $view->with('carrinhoContagem', $count);
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
