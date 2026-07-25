<?php

namespace App\Http\Controllers\Empresa;

use App\Enums\Estoque\UnidadeMedida;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Insumo;
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

        return view('empresa.estoque.produto', [
            'empresa' => $empresa,
            'produto' => $produto,
            'movimentos' => $produto->estoqueMovimentos()->with('user')->limit(100)->get(),
            'limiarBaixo' => self::LIMIAR_BAIXO,
        ]);
    }

    /** Ficha técnica (receita): ingredientes, quantidades e modo de preparo. */
    public function ficha(Request $request, Produto $produto): View|RedirectResponse
    {
        $empresa = $this->autoriza($request, $produto);

        // Em alguns hostings Schema::hasTable falha; a query abaixo é a fonte da verdade.
        try {
            $produto->load('fichaTecnica.insumo');
            $insumosDisponiveis = Insumo::query()
                ->where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->orderBy('nome')
                ->get();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('empresa.estoque.produto', $produto)
                ->with('warning', 'Não foi possível abrir a ficha técnica. Confira migrate e o log.');
        }

        return view('empresa.estoque.ficha', [
            'empresa' => $empresa,
            'produto' => $produto,
            'ficha' => $produto->fichaTecnica,
            'insumosDisponiveis' => $insumosDisponiveis,
            'porcoesPossiveis' => $produto->porcoesPossiveisPelaFicha(),
            'insumoLimitante' => $produto->insumoLimitanteDaFicha(),
        ]);
    }

    public function fichaCabecalho(Request $request, Produto $produto): RedirectResponse
    {
        $this->autoriza($request, $produto);

        $data = $request->validate([
            'ficha_rendimento' => ['required', 'integer', 'min:1', 'max:10000'],
            'ficha_tempo_preparo_min' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'modo_preparo' => ['nullable', 'string', 'max:10000'],
        ]);

        $produto->update($data);

        return back()->with('status', 'Ficha técnica atualizada.');
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
            'insumo_id' => [
                'required',
                'integer',
                Rule::exists('insumos', 'id')->where('empresa_id', $empresa->id),
                Rule::unique('produto_ficha_itens', 'insumo_id')->where('produto_id', $produto->id),
            ],
            'quantidade' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'unidade' => ['required', Rule::enum(UnidadeMedida::class)],
            'observacao' => ['nullable', 'string', 'max:200'],
        ], [
            'insumo_id.unique' => 'Este ingrediente já está na ficha técnica.',
        ]);

        $insumo = Insumo::query()->where('empresa_id', $empresa->id)->findOrFail($data['insumo_id']);
        $unidade = UnidadeMedida::from($data['unidade']);

        ProdutoFichaItem::query()->create([
            'empresa_id' => $empresa->id,
            'produto_id' => $produto->id,
            'insumo_id' => $insumo->id,
            'quantidade' => (float) $data['quantidade'],
            'unidade' => $unidade,
            'quantidade_base' => $this->estoque->converterParaBase((float) $data['quantidade'], $unidade, $insumo),
            'observacao' => $data['observacao'] ?? null,
            'ordem' => (int) ProdutoFichaItem::query()->where('produto_id', $produto->id)->max('ordem') + 1,
        ]);

        return back()->with('status', 'Ingrediente adicionado à ficha técnica.');
    }

    public function fichaUpdate(Request $request, Produto $produto, ProdutoFichaItem $fichaItem): RedirectResponse
    {
        $this->autoriza($request, $produto);
        abort_unless((int) $fichaItem->produto_id === (int) $produto->id, 403);

        $data = $request->validate([
            'quantidade' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'unidade' => ['required', Rule::enum(UnidadeMedida::class)],
            'observacao' => ['nullable', 'string', 'max:200'],
        ]);

        $unidade = UnidadeMedida::from($data['unidade']);
        $insumo = $fichaItem->insumo;

        $fichaItem->update([
            'quantidade' => (float) $data['quantidade'],
            'unidade' => $unidade,
            'quantidade_base' => $this->estoque->converterParaBase((float) $data['quantidade'], $unidade, $insumo),
            'observacao' => $data['observacao'] ?? null,
        ]);

        return back()->with('status', 'Ingrediente atualizado.');
    }

    public function fichaDestroy(Request $request, Produto $produto, ProdutoFichaItem $fichaItem): RedirectResponse
    {
        $this->autoriza($request, $produto);
        abort_unless((int) $fichaItem->produto_id === (int) $produto->id, 403);

        $fichaItem->delete();

        return back()->with('status', 'Ingrediente removido da ficha técnica.');
    }

    private function autoriza(Request $request, Produto $produto): Empresa
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa && (int) $produto->empresa_id === (int) $empresa->id, 403);

        return $empresa;
    }
}
