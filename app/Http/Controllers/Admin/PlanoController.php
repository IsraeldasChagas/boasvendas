<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plano;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanoController extends Controller
{
    public function index(): View
    {
        $planos = Plano::query()->orderBy('ordem')->orderBy('nome')->get();

        return view('admin.planos.index', compact('planos'));
    }

    public function create(): View
    {
        return view('admin.planos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Plano::query()->create($data);

        return redirect()
            ->route('admin.planos.index')
            ->with('status', 'Plano criado.');
    }

    public function edit(Plano $plano): View
    {
        return view('admin.planos.edit', compact('plano'));
    }

    public function update(Request $request, Plano $plano): RedirectResponse
    {
        $plano->update($this->validated($request));

        return redirect()
            ->route('admin.planos.index')
            ->with('status', 'Plano atualizado.');
    }

    public function destroy(Plano $plano): RedirectResponse
    {
        $plano->delete();

        return redirect()
            ->route('admin.planos.index')
            ->with('status', 'Plano removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'preco_mensal' => ['required', 'numeric', 'min:0'],
            'feature_primaria' => ['required', 'string', 'max:255'],
            'feature_secundaria' => ['required', 'string', 'max:255'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);
        $data['ordem'] = (int) ($data['ordem'] ?? 0);
        $data['ativo'] = $request->boolean('ativo');

        return $data;
    }
}
