<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\CaixaMovimento;
use App\Models\Cliente;
use App\Models\FinanceiroTitulo;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public const MAX_DIAS_PERIODO = 120;

    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa para ver relatórios.');
        }

        $fim = Carbon::parse($request->input('fim', Carbon::today()->toDateString()))->endOfDay();
        $inicio = Carbon::parse($request->input('inicio', $fim->copy()->subDays(29)->toDateString()))->startOfDay();

        if ($fim->lt($inicio)) {
            [$inicio, $fim] = [$fim->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        if ($inicio->diffInDays($fim) > static::MAX_DIAS_PERIODO) {
            $inicio = $fim->copy()->subDays(static::MAX_DIAS_PERIODO)->startOfDay();
        }

        $empresaId = $empresa->id;

        $totalRecebidoPeriodo = (float) FinanceiroTitulo::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', FinanceiroTitulo::TIPO_RECEBER)
            ->where('status', FinanceiroTitulo::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $totalPagoPeriodo = (float) FinanceiroTitulo::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', FinanceiroTitulo::TIPO_PAGAR)
            ->where('status', FinanceiroTitulo::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $totalVendasCaixaPeriodo = (float) CaixaMovimento::query()
            ->where('tipo', CaixaMovimento::TIPO_VENDA_AVULSA)
            ->whereBetween('created_at', [$inicio, $fim])
            ->whereHas('turno', fn ($q) => $q->where('empresa_id', $empresaId))
            ->sum('valor');

        $novosClientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$inicio, $fim])
            ->count();

        $hoje = Carbon::today();
        $titulosAtrasados = FinanceiroTitulo::query()
            ->where('empresa_id', $empresaId)
            ->where('status', FinanceiroTitulo::STATUS_ABERTO)
            ->whereDate('vencimento', '<', $hoje)
            ->orderBy('vencimento')
            ->limit(25)
            ->get();

        $valorAtrasado = (float) FinanceiroTitulo::query()
            ->where('empresa_id', $empresaId)
            ->where('status', FinanceiroTitulo::STATUS_ABERTO)
            ->whereDate('vencimento', '<', $hoje)
            ->sum('valor');

        $produtosEstoqueBaixo = Produto::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where('estoque', '<=', 10)
            ->orderBy('estoque')
            ->orderBy('nome')
            ->limit(20)
            ->get();

        $chartLabels = [];
        $serieRecebido = [];
        $seriePago = [];
        $serieCaixa = [];

        $recRows = FinanceiroTitulo::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', FinanceiroTitulo::TIPO_RECEBER)
            ->where('status', FinanceiroTitulo::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio->toDateString(), $fim->toDateString()])
            ->get(['pago_em', 'valor']);

        $pagRows = FinanceiroTitulo::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', FinanceiroTitulo::TIPO_PAGAR)
            ->where('status', FinanceiroTitulo::STATUS_PAGO)
            ->whereBetween('pago_em', [$inicio->toDateString(), $fim->toDateString()])
            ->get(['pago_em', 'valor']);

        $cxRows = CaixaMovimento::query()
            ->where('tipo', CaixaMovimento::TIPO_VENDA_AVULSA)
            ->whereBetween('created_at', [$inicio, $fim])
            ->whereHas('turno', fn ($q) => $q->where('empresa_id', $empresaId))
            ->get(['created_at', 'valor']);

        $mapRec = [];
        foreach ($recRows as $t) {
            $k = $t->pago_em->format('Y-m-d');
            $mapRec[$k] = ($mapRec[$k] ?? 0) + (float) $t->valor;
        }
        $mapPag = [];
        foreach ($pagRows as $t) {
            $k = $t->pago_em->format('Y-m-d');
            $mapPag[$k] = ($mapPag[$k] ?? 0) + (float) $t->valor;
        }
        $mapCx = [];
        foreach ($cxRows as $m) {
            $k = $m->created_at->format('Y-m-d');
            $mapCx[$k] = ($mapCx[$k] ?? 0) + (float) $m->valor;
        }

        $chartMax = 1.0;
        for ($d = $inicio->copy()->startOfDay(); $d->lte($fim); $d->addDay()) {
            $k = $d->format('Y-m-d');
            $chartLabels[] = $d->format('d/m');
            $r = $mapRec[$k] ?? 0;
            $p = $mapPag[$k] ?? 0;
            $c = $mapCx[$k] ?? 0;
            $serieRecebido[] = $r;
            $seriePago[] = $p;
            $serieCaixa[] = $c;
            $chartMax = max($chartMax, $r, $p, $c);
        }

        return view('empresa.relatorios.index', compact(
            'empresa',
            'inicio',
            'fim',
            'totalRecebidoPeriodo',
            'totalPagoPeriodo',
            'totalVendasCaixaPeriodo',
            'novosClientes',
            'titulosAtrasados',
            'valorAtrasado',
            'produtosEstoqueBaixo',
            'chartLabels',
            'serieRecebido',
            'seriePago',
            'serieCaixa',
            'chartMax'
        ));
    }
}
