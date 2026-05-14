<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Enums\Mesas\ComandaItemStatus;
use App\Enums\Mesas\ComandaSetorDestino;
use App\Models\ComandaItem;
use App\Services\Mesas\CozinhaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CozinhaController extends Controller
{
    public function __construct(
        private readonly CozinhaService $cozinhaService,
    ) {}

    public function index(Request $request): View
    {
        $empresaId = (int) $request->user()->empresa_id;
        $setor = $request->query('setor');
        $setorEnum = null;
        if (is_string($setor) && $setor !== '' && $setor !== 'todos') {
            try {
                $setorEnum = ComandaSetorDestino::from($setor);
            } catch (\ValueError) {
                $setorEnum = null;
            }
        }

        $itens = $this->cozinhaService->itensPainel($empresaId, $setorEnum);

        return view('mesas.cozinha', [
            'itens' => $itens,
            'setorFiltro' => $setor ?? 'todos',
        ]);
    }

    public function updateStatus(Request $request, ComandaItem $comandaItem): RedirectResponse
    {
        $this->authorizeItem($request, $comandaItem);
        $data = $request->validate([
            'status' => ['required', 'string', 'in:recebido,em_preparo,pronto,entregue'],
        ]);

        try {
            $this->cozinhaService->atualizarStatusItem($comandaItem, ComandaItemStatus::from($data['status']));
        } catch (\Throwable $e) {
            return redirect()->route('empresa.mesas.cozinha')->with('error', $e->getMessage());
        }

        return redirect()->route('empresa.mesas.cozinha')->with('success', 'Status atualizado.');
    }

    private function authorizeItem(Request $request, ComandaItem $comandaItem): void
    {
        $comandaItem->loadMissing('comanda');
        if (! $comandaItem->comanda || (int) $comandaItem->comanda->empresa_id !== (int) $request->user()->empresa_id) {
            abort(403);
        }
    }
}
