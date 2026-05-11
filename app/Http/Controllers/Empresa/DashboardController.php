<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\SuporteTicket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $empresa = auth()->user()->empresa?->load('plano');

        $assinatura = null;
        $ticketsAbertos = 0;
        $ticketsRecentes = collect();
        $chartHeights = [];
        $pedidosPendentesConfirmacao = 0;
        $vendaDiaTotal = 0.0;
        $produtosDistintosVendidosDia = 0;
        $unidadesVendidasDia = 0;

        if ($empresa) {
            $assinatura = Assinatura::query()
                ->where('empresa_id', $empresa->id)
                ->orderByDesc('id')
                ->first();

            if (! $assinatura) {
                $assinatura = Assinatura::query()
                    ->where('empresa_nome', $empresa->nome)
                    ->orderByDesc('id')
                    ->first();
            }

            $ticketsAbertos = SuporteTicket::query()
                ->where('empresa_id', $empresa->id)
                ->whereIn('status', ['aberto', 'aguardando', 'em_andamento'])
                ->count();

            $ticketsRecentes = SuporteTicket::query()
                ->where('empresa_id', $empresa->id)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get();

            $counts = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i)->toDateString();
                $counts[] = SuporteTicket::query()
                    ->where('empresa_id', $empresa->id)
                    ->whereDate('created_at', $day)
                    ->count();
            }
            $max = max($counts) ?: 1;
            $chartHeights = array_map(fn (int $c): int => (int) round(($c / $max) * 100), $counts);

            $pedidosPendentesConfirmacao = Pedido::query()
                ->where('empresa_id', $empresa->id)
                ->where('status', Pedido::STATUS_PENDENTE_LOJA)
                ->count();

            $inicioDia = now()->startOfDay();
            $fimDia = now()->endOfDay();

            $vendaDiaTotal = (float) Pedido::query()
                ->where('empresa_id', $empresa->id)
                ->where('status', '!=', Pedido::STATUS_CANCELADO)
                ->whereBetween('created_at', [$inicioDia, $fimDia])
                ->sum('total');

            $baseItensDia = PedidoItem::query()
                ->join('pedidos', 'pedido_itens.pedido_id', '=', 'pedidos.id')
                ->where('pedidos.empresa_id', $empresa->id)
                ->where('pedidos.status', '!=', Pedido::STATUS_CANCELADO)
                ->whereBetween('pedidos.created_at', [$inicioDia, $fimDia]);

            // Tipos distintos: com produto_id agrupa por produto; sem ID conta cada linha.
            $comId = (int) (clone $baseItensDia)
                ->whereNotNull('pedido_itens.produto_id')
                ->selectRaw('COUNT(DISTINCT pedido_itens.produto_id) as c')
                ->value('c');
            $semId = (int) (clone $baseItensDia)
                ->whereNull('pedido_itens.produto_id')
                ->selectRaw('COUNT(DISTINCT pedido_itens.id) as c')
                ->value('c');
            $produtosDistintosVendidosDia = $comId + $semId;

            $unidadesVendidasDia = (int) (clone $baseItensDia)->sum('pedido_itens.quantidade');
        }

        return view('empresa.dashboard', compact(
            'empresa',
            'assinatura',
            'ticketsAbertos',
            'ticketsRecentes',
            'chartHeights',
            'pedidosPendentesConfirmacao',
            'vendaDiaTotal',
            'produtosDistintosVendidosDia',
            'unidadesVendidasDia'
        ));
    }
}
