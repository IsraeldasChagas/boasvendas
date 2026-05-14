<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Comanda;
use App\Models\Mesa;
use App\Models\MesaConfiguracao;
use App\Services\Mesas\ComandaService;
use App\Services\Mesas\MesaService;
use App\Services\Mesas\PagamentoComandaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FechamentoMesaController extends Controller
{
    public function __construct(
        private readonly MesaService $mesaService,
        private readonly PagamentoComandaService $pagamentoComandaService,
        private readonly ComandaService $comandaService,
    ) {}

    public function index(Request $request): View
    {
        $empresaId = (int) $request->user()->empresa_id;
        $comandas = Comanda::query()
            ->daEmpresa($empresaId)
            ->whereIn('status', [\App\Enums\Mesas\ComandaStatus::ContaSolicitada, \App\Enums\Mesas\ComandaStatus::EmConsumo])
            ->with(['mesa', 'garcom'])
            ->orderByRaw("CASE status WHEN 'conta_solicitada' THEN 0 WHEN 'em_consumo' THEN 1 ELSE 2 END")
            ->orderByDesc('aberta_em')
            ->get();

        return view('mesas.fechamento-index', [
            'comandas' => $comandas,
        ]);
    }

    public function show(Request $request, Comanda $comanda): View|RedirectResponse
    {
        $this->authorizeComanda($request, $comanda);
        if (in_array($comanda->status, [\App\Enums\Mesas\ComandaStatus::Fechada, \App\Enums\Mesas\ComandaStatus::Cancelada], true)) {
            return redirect()->route('empresa.mesas.index')->with('error', 'Esta comanda já foi encerrada.');
        }
        $comanda->load(['mesa', 'garcom', 'itens', 'pagamentos']);
        $config = MesaConfiguracao::obterOuCriarPadrao((int) $comanda->empresa_id, $comanda->unidade_id !== null ? (int) $comanda->unidade_id : null);
        $formas = collect(\App\Models\Pedido::formasPagamentoRotulos())
            ->except([\App\Models\Pedido::PAGAMENTO_ENTREGA])
            ->all();

        return view('mesas.fechamento', [
            'comanda' => $comanda,
            'config' => $config,
            'formasPagamento' => $formas,
            'subtotalItens' => (float) $this->comandaService->subtotalItens($comanda),
        ]);
    }

    public function redirectFromMesa(Request $request, Mesa $mesa): RedirectResponse
    {
        if ((int) $mesa->empresa_id !== (int) $request->user()->empresa_id) {
            abort(403);
        }
        $c = $this->mesaService->comandaAbertaNaMesa($mesa);
        if (! $c) {
            return redirect()->route('empresa.mesas.index')->with('error', 'Esta mesa não possui comanda aberta.');
        }

        return redirect()->route('empresa.mesas.fechamento.show', $c);
    }

    public function finalizar(Request $request, Comanda $comanda): RedirectResponse
    {
        $this->authorizeComanda($request, $comanda);
        $data = $request->validate([
            'taxa_servico_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'desconto' => ['nullable', 'numeric', 'min:0'],
            'pagamentos' => ['required', 'array', 'min:1'],
            'pagamentos.*.forma_pagamento' => ['required', 'string', 'max:48'],
            'pagamentos.*.valor_pago' => ['required', 'numeric', 'min:0'],
            'pagamentos.*.troco' => ['nullable', 'numeric', 'min:0'],
        ]);

        $taxa = isset($data['taxa_servico_percentual']) && $data['taxa_servico_percentual'] !== ''
            ? (float) $data['taxa_servico_percentual']
            : null;
        $desconto = (float) ($data['desconto'] ?? 0);

        $pagamentos = [];
        foreach ($data['pagamentos'] as $row) {
            $pagamentos[] = [
                'forma_pagamento' => $row['forma_pagamento'],
                'valor_pago' => $row['valor_pago'],
                'troco' => $row['troco'] ?? 0,
            ];
        }

        try {
            $this->pagamentoComandaService->finalizarComanda(
                $comanda,
                $taxa,
                $desconto,
                $pagamentos,
                (int) $request->user()->id,
            );
        } catch (\Throwable $e) {
            return redirect()->route('empresa.mesas.fechamento.show', $comanda)->with('error', $e->getMessage());
        }

        return redirect()->route('empresa.mesas.index')->with('success', 'Pagamento registrado e mesa liberada.');
    }

    private function authorizeComanda(Request $request, Comanda $comanda): void
    {
        if ((int) $comanda->empresa_id !== (int) $request->user()->empresa_id) {
            abort(403);
        }
    }
}
