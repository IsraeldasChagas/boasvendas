<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Mesa;
use App\Models\Produto;
use App\Models\User;
use App\Enums\Mesas\ComandaSetorDestino;
use App\Services\Mesas\ComandaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComandaController extends Controller
{
    public function __construct(
        private readonly ComandaService $comandaService,
    ) {}

    public function indexAbertas(Request $request): View
    {
        $empresaId = (int) $request->user()->empresa_id;
        $comandas = Comanda::query()
            ->daEmpresa($empresaId)
            ->whereIn('status', \App\Enums\Mesas\ComandaStatus::abertasValues())
            ->with(['mesa', 'garcom'])
            ->orderByDesc('aberta_em')
            ->get();

        return view('mesas.comandas-abertas', [
            'comandas' => $comandas,
        ]);
    }

    public function show(Request $request, Comanda $comanda): View
    {
        $this->authorizeComanda($request, $comanda);
        $comanda->load(['mesa', 'garcom', 'itens' => fn ($q) => $q->orderBy('id')]);
        $produtos = Produto::query()
            ->where('empresa_id', $comanda->empresa_id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'preco']);
        $garcons = User::query()
            ->where('empresa_id', $comanda->empresa_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('mesas.comanda', [
            'comanda' => $comanda,
            'produtos' => $produtos,
            'garcons' => $garcons,
            'setores' => ComandaSetorDestino::cases(),
        ]);
    }

    public function preConta(Request $request, Comanda $comanda): View
    {
        $this->authorizeComanda($request, $comanda);
        $comanda->load(['mesa', 'itens', 'garcom']);

        return view('mesas.pre-conta', [
            'comanda' => $comanda,
        ]);
    }

    public function adicionarItem(Request $request, Comanda $comanda): RedirectResponse
    {
        $this->authorizeComanda($request, $comanda);
        $data = $request->validate([
            'produto_id' => ['required', 'integer', 'exists:produtos,id'],
            'quantidade' => ['required', 'integer', 'min:1', 'max:999'],
            'observacao' => ['nullable', 'string', 'max:500'],
            'setor_destino' => ['required', 'string', 'in:cozinha,bar,caixa'],
        ]);

        try {
            $this->comandaService->adicionarItem(
                $comanda,
                (int) $data['produto_id'],
                (int) $data['quantidade'],
                $data['observacao'] ?? null,
                ComandaSetorDestino::from($data['setor_destino']),
            );
        } catch (\Throwable $e) {
            return redirect()->route('empresa.comandas.show', $comanda)->with('error', $e->getMessage());
        }

        return redirect()->route('empresa.comandas.show', $comanda)->with('success', 'Item adicionado.');
    }

    public function removerItem(Request $request, Comanda $comanda, ComandaItem $comandaItem): RedirectResponse
    {
        $this->authorizeComanda($request, $comanda);
        if ((int) $comandaItem->comanda_id !== (int) $comanda->id) {
            abort(404);
        }
        try {
            $this->comandaService->removerItem($comandaItem);
        } catch (\Throwable $e) {
            return redirect()->route('empresa.comandas.show', $comanda)->with('error', $e->getMessage());
        }

        return redirect()->route('empresa.comandas.show', $comanda)->with('success', 'Item removido.');
    }

    public function enviarCozinha(Request $request, Comanda $comanda): RedirectResponse
    {
        $this->authorizeComanda($request, $comanda);
        try {
            $n = $this->comandaService->enviarPendentesParaCozinha($comanda);
        } catch (\Throwable $e) {
            return redirect()->route('empresa.comandas.show', $comanda)->with('error', $e->getMessage());
        }

        $msg = $n > 0 ? "{$n} item(ns) enviado(s) à cozinha/bar." : 'Nenhum item pendente para enviar.';

        return redirect()->route('empresa.comandas.show', $comanda)->with('success', $msg);
    }

    public function atualizarCabecalho(Request $request, Comanda $comanda): RedirectResponse
    {
        $this->authorizeComanda($request, $comanda);
        $data = $request->validate([
            'cliente_nome' => ['nullable', 'string', 'max:160'],
            'cliente_documento' => ['nullable', 'string', 'max:32'],
            'garcom_id' => ['nullable', 'integer', 'exists:users,id'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ]);

        $garcomId = isset($data['garcom_id']) ? (int) $data['garcom_id'] : null;
        if ($request->user()->temAcessoRestritoAoPainelEmpresa()) {
            $garcomId = (int) $request->user()->id;
        }

        try {
            $this->comandaService->atualizarCabecalho(
                $comanda,
                $data['cliente_nome'] ?? null,
                $data['cliente_documento'] ?? null,
                $garcomId,
                $data['observacao'] ?? null,
            );
        } catch (\Throwable $e) {
            return redirect()->route('empresa.comandas.show', $comanda)->with('error', $e->getMessage());
        }

        return redirect()->route('empresa.comandas.show', $comanda)->with('success', 'Dados atualizados.');
    }

    public function fiscalFuturo(Request $request, Comanda $comanda): RedirectResponse
    {
        $this->authorizeComanda($request, $comanda);

        return redirect()
            ->route('empresa.comandas.show', $comanda)
            ->with('error', 'Emissão fiscal (NFC-e) será integrada ao módulo fiscal em versão futura.');
    }

    private function authorizeComanda(Request $request, Comanda $comanda): void
    {
        if ((int) $comanda->empresa_id !== (int) $request->user()->empresa_id) {
            abort(403);
        }
    }
}
