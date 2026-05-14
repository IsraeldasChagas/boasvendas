<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\FidelidadeCartao;
use App\Models\FidelidadePontosHistorico;
use App\Models\FidelidadePrograma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FidelidadeController extends Controller
{
    public function programa(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $programa = $empresa->fidelidadePrograma;
        if (! $programa) {
            $programa = new FidelidadePrograma([
                'empresa_id' => $empresa->id,
                'ativo' => false,
                'nome_exibicao' => 'Cartão fidelidade',
                'pedidos_meta' => 10,
                'tipo_recompensa' => FidelidadePrograma::TIPO_PRODUTO,
            ]);
        }

        $produtos = $empresa->produtos()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('empresa.fidelidade.programa', compact('empresa', 'programa', 'produtos'));
    }

    public function programaUpdate(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $request->validate([
            'nome_exibicao' => ['required', 'string', 'max:120'],
            'pedidos_meta' => ['required', 'integer', 'min:1', 'max:100'],
            'tipo_recompensa' => ['required', Rule::in([FidelidadePrograma::TIPO_PRODUTO, FidelidadePrograma::TIPO_DESCONTO_VALOR])],
            'produto_id' => [
                'nullable',
                'integer',
                Rule::exists('produtos', 'id')->where('empresa_id', $empresa->id),
            ],
            'valor_desconto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'texto_recompensa' => ['nullable', 'string', 'max:500'],
        ]);

        $data['ativo'] = $request->boolean('ativo');

        if ($data['tipo_recompensa'] === FidelidadePrograma::TIPO_PRODUTO) {
            $extra = $request->validate([
                'produto_id' => ['required', 'integer', Rule::exists('produtos', 'id')->where('empresa_id', $empresa->id)],
            ]);
            $data['produto_id'] = $extra['produto_id'];
            $data['valor_desconto'] = null;
        } else {
            $extra = $request->validate([
                'valor_desconto' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            ]);
            $data['valor_desconto'] = $extra['valor_desconto'];
            $data['produto_id'] = null;
        }

        FidelidadePrograma::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            $data + ['empresa_id' => $empresa->id]
        );

        return redirect()
            ->route('empresa.fidelidade.programa')
            ->with('status', 'Programa de fidelidade salvo.');
    }

    public function cartoes(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $programa = $empresa->fidelidadePrograma;
        $q = $request->string('q')->trim()->value();
        $qNorm = FidelidadeCartao::normalizarTelefone($q);
        $statusCartao = $request->string('status')->trim()->value();

        $cartoes = FidelidadeCartao::query()
            ->with('cliente')
            ->where('empresa_id', $empresa->id)
            ->when($q !== '', function ($sub) use ($q, $qNorm) {
                $sub->where(function ($w) use ($q, $qNorm) {
                    $w->where('codigo_fidelidade', 'like', '%'.$q.'%');
                    if ($qNorm !== '') {
                        $w->orWhere('telefone_normalizado', 'like', '%'.$qNorm.'%');
                    }
                    $w->orWhereHas('cliente', function ($c) use ($q) {
                        $c->where('nome', 'like', '%'.$q.'%');
                    });
                });
            })
            ->when(in_array($statusCartao, [FidelidadeCartao::STATUS_ATIVO, FidelidadeCartao::STATUS_INATIVO], true), function ($sub) use ($statusCartao) {
                $sub->where('status', $statusCartao);
            })
            ->orderByDesc('updated_at')
            ->limit(120)
            ->get();

        return view('empresa.fidelidade.cartoes', compact('empresa', 'programa', 'cartoes', 'q', 'statusCartao'));
    }

    public function cartoesHistorico(Request $request, FidelidadeCartao $fidelidadeCartao): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $fidelidadeCartao->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $historicos = $fidelidadeCartao->historicosPontos()->limit(200)->get();

        return view('empresa.fidelidade.cartoes-historico', compact('empresa', 'fidelidadeCartao', 'historicos'));
    }

    public function cartoesDestroy(Request $request, FidelidadeCartao $fidelidadeCartao): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $fidelidadeCartao->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $telNorm = $fidelidadeCartao->telefone_normalizado;
        $fidelidadeCartao->delete();

        return redirect()
            ->route('empresa.fidelidade.cartoes', ['q' => $telNorm])
            ->with('status', 'Cartão excluído. O cliente pode cadastrar de novo com o mesmo telefone, se desejar.');
    }

    public function cartoesUpdatePontos(Request $request, FidelidadeCartao $fidelidadeCartao): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $fidelidadeCartao->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $data = $request->validate([
            'pontos' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        $novo = (int) $data['pontos'];
        $antigo = (int) $fidelidadeCartao->pontos;
        $delta = $novo - $antigo;
        if ($delta !== 0) {
            $fidelidadeCartao->pontos = $novo;
            $fidelidadeCartao->save();
            $fidelidadeCartao->registrarHistorico(
                FidelidadePontosHistorico::TIPO_AJUSTE,
                $delta,
                'Ajuste manual de pontos (de '.$antigo.' para '.$novo.').'
            );
        }

        return redirect()
            ->route('empresa.fidelidade.cartoes', ['q' => $fidelidadeCartao->telefone_normalizado])
            ->with('status', 'Pontos atualizados.');
    }

    public function cartoesToggleStatus(Request $request, FidelidadeCartao $fidelidadeCartao): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $fidelidadeCartao->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $novo = $fidelidadeCartao->estaAtivo()
            ? FidelidadeCartao::STATUS_INATIVO
            : FidelidadeCartao::STATUS_ATIVO;
        $fidelidadeCartao->status = $novo;
        $fidelidadeCartao->save();
        $fidelidadeCartao->registrarHistorico(
            FidelidadePontosHistorico::TIPO_STATUS,
            0,
            $novo === FidelidadeCartao::STATUS_ATIVO ? 'Cartão ativado' : 'Cartão inativado'
        );

        return redirect()
            ->route('empresa.fidelidade.cartoes', ['q' => $fidelidadeCartao->telefone_normalizado])
            ->with('status', $novo === FidelidadeCartao::STATUS_ATIVO ? 'Cartão ativado.' : 'Cartão inativado.');
    }

    public function adicionarSelo(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $programa = $empresa->fidelidadePrograma;
        if (! $programa || ! $programa->ativo) {
            return redirect()
                ->route('empresa.fidelidade.cartoes')
                ->with('warning', 'Ative o programa de fidelidade antes de lançar selos.');
        }

        $data = $request->validate([
            'telefone' => ['required', 'string', 'min:8', 'max:32'],
        ]);

        $norm = FidelidadeCartao::normalizarTelefone($data['telefone']);
        if (strlen($norm) < 8) {
            return back()->withErrors(['telefone' => 'Informe um telefone válido.'])->withInput();
        }

        $clienteId = $this->resolverClienteIdPorTelefone($empresa->id, $norm);

        $cartao = FidelidadeCartao::query()->firstOrCreate(
            [
                'empresa_id' => $empresa->id,
                'telefone_normalizado' => $norm,
            ],
            [
                'cliente_id' => $clienteId,
                'status' => FidelidadeCartao::STATUS_ATIVO,
                'pontos' => 0,
            ]
        );

        if ($clienteId && ! $cartao->cliente_id) {
            $cartao->update(['cliente_id' => $clienteId]);
        }

        $cartao->increment('selos');
        $cartao->increment('pontos');
        $cartao->refresh();
        $cartao->registrarHistorico(FidelidadePontosHistorico::TIPO_SELO, 1, 'Selo por compra (loja)');

        return redirect()
            ->route('empresa.fidelidade.cartoes', ['q' => $norm])
            ->with('status', 'Selo registrado para o telefone informado.');
    }

    public function resgatar(Request $request, FidelidadeCartao $fidelidadeCartao): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $fidelidadeCartao->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $programa = $empresa->fidelidadePrograma;
        if (! $programa || ! $programa->ativo) {
            return redirect()->route('empresa.fidelidade.cartoes')->with('warning', 'Programa inativo.');
        }

        if (! $fidelidadeCartao->podeResgatar($programa)) {
            return back()->with('warning', 'Este cartão ainda não atingiu a meta de selos.');
        }

        $meta = (int) $programa->pedidos_meta;
        $fidelidadeCartao->selos -= $meta;
        $fidelidadeCartao->pontos = max(0, (int) $fidelidadeCartao->pontos - $meta);
        $fidelidadeCartao->total_resgates += 1;
        $fidelidadeCartao->save();
        $fidelidadeCartao->registrarHistorico(
            FidelidadePontosHistorico::TIPO_RESGATE,
            -$meta,
            'Resgate de recompensa (meta de '.$meta.' selos)'
        );

        return redirect()
            ->route('empresa.fidelidade.cartoes', ['q' => $fidelidadeCartao->telefone_normalizado])
            ->with('status', 'Resgate registrado. Os selos foram debitados da meta.');
    }

    private function resolverClienteIdPorTelefone(int $empresaId, string $telefoneNormalizado): ?int
    {
        $clientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->whereNotNull('telefone')
            ->get(['id', 'telefone']);

        foreach ($clientes as $c) {
            if (FidelidadeCartao::normalizarTelefone($c->telefone) === $telefoneNormalizado) {
                return $c->id;
            }
        }

        return null;
    }
}
