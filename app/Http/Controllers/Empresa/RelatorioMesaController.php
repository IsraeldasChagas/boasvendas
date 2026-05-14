<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Comanda;
use App\Models\ComandaItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelatorioMesaController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = (int) $request->user()->empresa_id;
        $inicio = $request->query('inicio', now()->startOfMonth()->toDateString());
        $fim = $request->query('fim', now()->toDateString());

        $mesasAbertas = Comanda::query()
            ->daEmpresa($empresaId)
            ->whereIn('status', \App\Enums\Mesas\ComandaStatus::abertasValues())
            ->count();

        $mesasFechadasPeriodo = Comanda::query()
            ->daEmpresa($empresaId)
            ->where('status', \App\Enums\Mesas\ComandaStatus::Fechada)
            ->whereBetween('fechada_em', [$inicio.' 00:00:00', $fim.' 23:59:59'])
            ->count();

        $totalPeriodo = (float) Comanda::query()
            ->daEmpresa($empresaId)
            ->where('status', \App\Enums\Mesas\ComandaStatus::Fechada)
            ->whereBetween('fechada_em', [$inicio.' 00:00:00', $fim.' 23:59:59'])
            ->sum('total');

        $porGarcom = Comanda::query()
            ->daEmpresa($empresaId)
            ->where('status', \App\Enums\Mesas\ComandaStatus::Fechada)
            ->whereBetween('fechada_em', [$inicio.' 00:00:00', $fim.' 23:59:59'])
            ->selectRaw('garcom_id, COUNT(*) as qtd, SUM(total) as total_valor')
            ->groupBy('garcom_id')
            ->orderByDesc('total_valor')
            ->get();

        $garcons = \App\Models\User::query()
            ->where('empresa_id', $empresaId)
            ->pluck('name', 'id');

        $porMesa = Comanda::query()
            ->daEmpresa($empresaId)
            ->where('status', \App\Enums\Mesas\ComandaStatus::Fechada)
            ->whereBetween('fechada_em', [$inicio.' 00:00:00', $fim.' 23:59:59'])
            ->join('mesas', 'mesas.id', '=', 'comandas.mesa_id')
            ->selectRaw('mesas.numero, mesas.nome, COUNT(*) as qtd, SUM(comandas.total) as total_valor')
            ->groupBy('mesas.id', 'mesas.numero', 'mesas.nome')
            ->orderByDesc('total_valor')
            ->get();

        $produtosMaisVendidos = ComandaItem::query()
            ->join('comandas', 'comandas.id', '=', 'comanda_itens.comanda_id')
            ->where('comandas.empresa_id', $empresaId)
            ->where('comandas.status', \App\Enums\Mesas\ComandaStatus::Fechada)
            ->whereBetween('comandas.fechada_em', [$inicio.' 00:00:00', $fim.' 23:59:59'])
            ->where('comanda_itens.status', '!=', \App\Enums\Mesas\ComandaItemStatus::Cancelado->value)
            ->selectRaw('comanda_itens.nome_produto, SUM(comanda_itens.quantidade) as qtd, SUM(comanda_itens.valor_total) as total_valor')
            ->groupBy('comanda_itens.nome_produto')
            ->orderByDesc('qtd')
            ->limit(15)
            ->get();

        return view('mesas.relatorios', [
            'inicio' => $inicio,
            'fim' => $fim,
            'mesasAbertas' => $mesasAbertas,
            'mesasFechadasPeriodo' => $mesasFechadasPeriodo,
            'totalPeriodo' => $totalPeriodo,
            'porGarcom' => $porGarcom,
            'garcons' => $garcons,
            'porMesa' => $porMesa,
            'produtosMaisVendidos' => $produtosMaisVendidos,
        ]);
    }
}
