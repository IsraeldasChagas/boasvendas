<?php

namespace App\Http\Controllers\Empresa\Fiscal;

use App\Enums\Fiscal\FiscalNotaStatus;
use App\Http\Controllers\Controller;
use App\Models\FiscalNota;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalNotaController extends Controller
{
    public function emitidas(Request $request): View
    {
        return $this->listar($request, 'emitidas', [FiscalNotaStatus::Autorizada]);
    }

    public function pendentes(Request $request): View
    {
        return $this->listar($request, 'pendentes', [
            FiscalNotaStatus::AguardandoEmissao,
            FiscalNotaStatus::Processando,
        ]);
    }

    public function rejeicoes(Request $request): View
    {
        return $this->listar($request, 'rejeicoes', [FiscalNotaStatus::Rejeitada]);
    }

    /**
     * @param  list<FiscalNotaStatus>  $statuses
     */
    private function listar(Request $request, string $aba, array $statuses): View
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $q = $request->string('q')->trim()->value();

        $notas = FiscalNota::query()
            ->with(['pedido'])
            ->where('empresa_id', $empresa->id)
            ->whereIn('status', array_map(static fn (FiscalNotaStatus $s) => $s->value, $statuses))
            ->when($q !== '', function ($sub) use ($q) {
                $sub->where(function ($w) use ($q) {
                    $w->where('numero_nota', 'like', '%'.$q.'%')
                        ->orWhere('chave_acesso', 'like', '%'.$q.'%')
                        ->orWhere('serie', 'like', '%'.$q.'%')
                        ->orWhereHas('pedido', function ($p) use ($q) {
                            $p->where('codigo_publico', 'like', '%'.$q.'%');
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(30)
            ->withQueryString();

        return view('empresa.fiscal.notas.index', compact('empresa', 'notas', 'aba'));
    }
}
