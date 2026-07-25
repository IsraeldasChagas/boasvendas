<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\ProdutoFichaItem;
use App\Services\Estoque\EstoqueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EstoqueController extends Controller
{
    public const LIMIAR_BAIXO = 10;

    public function __construct(private readonly EstoqueService $estoque) {}

    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa para ver o estoque.');
        }

        $query = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->with('categoria')
            ->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%');
            });
        }

        if ($request->input('filtro') === 'baixo') {
            $query->where('estoque', '<=', self::LIMIAR_BAIXO);
            if (Schema::hasColumn('produtos', 'controla_estoque')) {
                $query->where('controla_estoque', true);
            }
        }

        $produtos = $query->get();

        $baixoCount = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('estoque', '<=', self::LIMIAR_BAIXO)
            ->when(Schema::hasColumn('produtos', 'controla_estoque'), fn ($q2) => $q2->where('controla_estoque', true))
            ->count();

        return view('empresa.estoque.index', [
            'empresa' => $empresa,
            'produtos' => $produtos,
            'baixoCount' => $baixoCount,
            'limiarBaixo' => self::LIMIAR_BAIXO,
        ]);
    }

    public function produto(Request $request, Produto $produto): View
    {
        $empresa = $this->autoriza($request, $produto);

        $movimentos = $produto->estoqueMovimentos()->with('user')->limit(100)->get();
        $ficha = Schema::hasTable('produto_ficha_itens') ? $produto->fichaTecnica()->get() : collect();

        $insumosDisponiveis = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', '!=', $produto->id)
            ->orderBy('nome')
            ->get(['id', 'nome', 'estoque']);

        return view('empresa.estoque.produto', [
            'empresa' => $empresa,
            'produto' => $produto,
            'movimentos' => $movimentos,
            'ficha' => $ficha,
            'insumosDisponiveis' => $insumosDisponiveis,
            'limiarBaixo' => self::LIMIAR_BAIXO,
        ]);
    }

    public function repor(Request $request, Produto $produto): RedirectResponse
    {
        $this->autoriza($request, $produto);

        $data = $request->validate([
            'quantidade' => ['required', 'integer', 'min:1', 'max:1000000'],
            'observacao' => ['nullable', 'string', 'max:300'],
        ]);

        $this->estoque->repor($produto, (int) $data['quantidade'], $data['observacao'] ?? null, $request->user()?->id);

        return back()->with('status', 'Estoque reposto: +'.$data['quantidade'].' em "'.$produto->nome.'".');
    }

    public function ajustar(Request $request, Produto $produto): RedirectResponse
    {
        $this->autoriza($request, $produto);

        $data = $request->validate([
            'novo_saldo' => ['required', 'integer', 'min:0', 'max:1000000'],
            'observacao' => ['nullable', 'string', 'max:300'],
        ]);

        $this->estoque->ajustar($produto, (int) $data['novo_saldo'], $data['observacao'] ?? null, $request->user()?->id);

        return back()->with('status', 'Saldo de "'.$produto->nome.'" ajustado para '.$data['novo_saldo'].'.');
    }

    public function fichaStore(Request $request, Produto $produto): RedirectResponse
    {
        $empresa = $this->autoriza($request, $produto);

        $data = $request->validate([
            'insumo_produto_id' => [
                'required',
                'integer',
                Rule::exists('produtos', 'id')->where('empresa_id', $empresa->id),
                Rule::notIn([(string) $produto->id]),
                Rule::unique('produto_ficha_itens', 'insumo_produto_id')->where('produto_id', $produto->id),
            ],
            'quantidade' => ['required', 'integer', 'min:1', 'max:1000'],
        ], [
            'insumo_produto_id.not_in' => 'O produto não pode ser insumo de si mesmo.',
            'insumo_produto_id.unique' => 'Este insumo já está na ficha técnica.',
        ]);

        ProdutoFichaItem::query()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produto->id,
            'insumo_produto_id' => (int) $data['insumo_produto_id'],
            'quantidade' => (int) $data['quantidade'],
        ]);

        return back()->with('status', 'Insumo adicionado à ficha técnica.');
    }

    public function fichaDestroy(Request $request, Produto $produto, ProdutoFichaItem $fichaItem): RedirectResponse
    {
        $this->autoriza($request, $produto);
        abort_unless((int) $fichaItem->produto_id === (int) $produto->id, 403);

        $fichaItem->delete();

        return back()->with('status', 'Insumo removido da ficha técnica.');
    }

    private function autoriza(Request $request, Produto $produto): \App\Models\Empresa
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa && (int) $produto->empresa_id === (int) $empresa->id, 403);

        return $empresa;
    }
}
