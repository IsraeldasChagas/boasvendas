<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModuloController extends Controller
{
    public function index(): View
    {
        $modulos = Modulo::query()->orderBy('ordem')->orderBy('nome')->get();

        return view('admin.modulos.index', compact('modulos'));
    }

    public function create(): View
    {
        return view('admin.modulos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Modulo::query()->create($this->validated($request));

        return redirect()
            ->route('admin.modulos.index')
            ->with('status', 'Módulo criado.');
    }

    public function edit(Modulo $modulo): View
    {
        return view('admin.modulos.edit', compact('modulo'));
    }

    public function update(Request $request, Modulo $modulo): RedirectResponse
    {
        $modulo->update($this->validated($request));

        return redirect()
            ->route('admin.modulos.index')
            ->with('status', 'Módulo atualizado.');
    }

    public function destroy(Modulo $modulo): RedirectResponse
    {
        $modulo->delete();

        return redirect()
            ->route('admin.modulos.index')
            ->with('status', 'Módulo removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'situacao' => ['required', 'string', Rule::in(array_keys(Modulo::situacoes()))],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);
        $data['categoria'] = $data['categoria'] ?? '';
        $data['ordem'] = (int) ($data['ordem'] ?? 0);

        return $data;
    }
}
