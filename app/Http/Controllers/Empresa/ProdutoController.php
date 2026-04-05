<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar produtos.');
        }

        $query = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->with('categoria')
            ->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhereHas('categoria', fn ($c) => $c->where('nome', 'like', '%'.$q.'%'));
            });
        }

        if ($request->filled('ativo')) {
            $ativo = $request->input('ativo');
            if ($ativo === '1') {
                $query->where('ativo', true);
            }
            if ($ativo === '0') {
                $query->where('ativo', false);
            }
        }

        $produtos = $query->get();

        return view('empresa.produtos.index', compact('empresa', 'produtos'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para cadastrar produtos.');
        }

        $categorias = Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('empresa.produtos.create', compact('empresa', 'categorias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request, $empresa);
        $data['empresa_id'] = $empresa->id;

        Produto::query()->create($data);

        return redirect()
            ->route('empresa.produtos.index')
            ->with('status', 'Produto cadastrado.');
    }

    public function edit(Request $request, Produto $produto): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $produto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $categorias = Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->where(function ($q) use ($produto) {
                $q->where('ativo', true);
                if ($produto->categoria_id) {
                    $q->orWhere('id', $produto->categoria_id);
                }
            })
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('empresa.produtos.edit', compact('empresa', 'produto', 'categorias'));
    }

    public function update(Request $request, Produto $produto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $produto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $produto->update($this->validated($request, $empresa, $produto));

        return redirect()
            ->route('empresa.produtos.index')
            ->with('status', 'Produto atualizado.');
    }

    public function destroy(Request $request, Produto $produto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $produto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $produto->delete();

        return redirect()
            ->route('empresa.produtos.index')
            ->with('status', 'Produto removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Empresa $empresa, ?Produto $produto = null): array
    {
        $skuRules = [
            'required',
            'string',
            'max:64',
            Rule::unique('produtos', 'sku')
                ->where(fn ($q) => $q->where('empresa_id', $empresa->id))
                ->ignore($produto?->id),
        ];

        $data = $request->validate([
            'sku' => $skuRules,
            'nome' => ['required', 'string', 'max:255'],
            'categoria_id' => [
                'nullable',
                'integer',
                Rule::exists('categorias', 'id')->where(fn ($q) => $q->where('empresa_id', $empresa->id)),
            ],
            'preco' => ['required', 'numeric', 'min:0'],
            'estoque' => ['required', 'integer', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'visivel_loja' => ['sometimes', 'boolean'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $data['visivel_loja'] = $request->boolean('visivel_loja');
        $data['ativo'] = $request->boolean('ativo');

        return $data;
    }
}
