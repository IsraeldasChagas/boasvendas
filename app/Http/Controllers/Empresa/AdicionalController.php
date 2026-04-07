<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdicionalController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar adicionais.');
        }

        $adicionais = Adicional::query()
            ->where('empresa_id', $empresa->id)
            ->withCount('produtos')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('empresa.adicionais.index', compact('empresa', 'adicionais'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.adicionais.create', compact('empresa'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request);
        $data['empresa_id'] = $empresa->id;
        if ($data['tipo'] === Adicional::TIPO_RETIRAR) {
            $data['preco'] = 0;
        }

        Adicional::query()->create($data);

        return redirect()
            ->route('empresa.adicionais.index')
            ->with('status', 'Adicional cadastrado.');
    }

    public function edit(Request $request, Adicional $adicional): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $adicional->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        return view('empresa.adicionais.edit', compact('empresa', 'adicional'));
    }

    public function update(Request $request, Adicional $adicional): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $adicional->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $data = $this->validated($request);
        if ($data['tipo'] === Adicional::TIPO_RETIRAR) {
            $data['preco'] = 0;
        }

        $adicional->update($data);

        return redirect()
            ->route('empresa.adicionais.index')
            ->with('status', 'Adicional atualizado.');
    }

    public function destroy(Request $request, Adicional $adicional): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $adicional->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $adicional->delete();

        return redirect()
            ->route('empresa.adicionais.index')
            ->with('status', 'Adicional removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'tipo' => ['required', 'string', Rule::in([Adicional::TIPO_ACRESCENTAR, Adicional::TIPO_RETIRAR])],
            'preco' => ['required', 'numeric', 'min:0'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $data['ativo'] = $request->boolean('ativo');
        $data['ordem'] = (int) ($data['ordem'] ?? 0);

        return $data;
    }
}
