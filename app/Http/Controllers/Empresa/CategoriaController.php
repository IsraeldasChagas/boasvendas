<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar categorias.');
        }

        $categorias = Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->withCount('produtos')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('empresa.categorias.index', compact('empresa', 'categorias'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.categorias.create', compact('empresa'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request, $empresa);
        $data['empresa_id'] = $empresa->id;
        Categoria::query()->create($data);

        return redirect()
            ->route('empresa.categorias.index')
            ->with('status', 'Categoria criada.');
    }

    public function edit(Request $request, Categoria $categoria): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $categoria->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        return view('empresa.categorias.edit', compact('empresa', 'categoria'));
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $categoria->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $categoria->update($this->validated($request, $empresa, $categoria));

        return redirect()
            ->route('empresa.categorias.index')
            ->with('status', 'Categoria atualizada.');
    }

    public function destroy(Request $request, Categoria $categoria): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $categoria->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        if ($categoria->produtos()->exists()) {
            return redirect()
                ->route('empresa.categorias.index')
                ->with('warning', 'Não é possível excluir: existem produtos nesta categoria. Reatribua os produtos ou remova-os primeiro.');
        }

        $categoria->delete();

        return redirect()
            ->route('empresa.categorias.index')
            ->with('status', 'Categoria removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Empresa $empresa, ?Categoria $categoria = null): array
    {
        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias', 'nome')
                    ->where(fn ($q) => $q->where('empresa_id', $empresa->id))
                    ->ignore($categoria?->id),
            ],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $data['ordem'] = (int) ($data['ordem'] ?? 0);
        $data['ativo'] = $request->boolean('ativo');

        return $data;
    }
}
