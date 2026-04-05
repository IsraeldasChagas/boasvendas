<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Plano;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssinaturaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Assinatura::query()->with('plano')->orderByDesc('proxima_cobranca');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where('empresa_nome', 'like', '%'.$q.'%');
        }

        $statusFiltro = $request->input('status');
        if (is_string($statusFiltro) && array_key_exists($statusFiltro, Assinatura::statusRotulos())) {
            $query->where('status', $statusFiltro);
        }

        $assinaturas = $query->get();

        return view('admin.assinaturas.index', compact('assinaturas'));
    }

    public function create(): View
    {
        $planos = Plano::query()->where('ativo', true)->orderBy('ordem')->orderBy('nome')->get();

        return view('admin.assinaturas.create', compact('planos'));
    }

    public function store(Request $request): RedirectResponse
    {
        Assinatura::query()->create($this->validated($request));

        return redirect()
            ->route('admin.assinaturas.index')
            ->with('status', 'Assinatura criada.');
    }

    public function edit(Assinatura $assinatura): View
    {
        $planos = Plano::query()->orderBy('ordem')->orderBy('nome')->get();

        return view('admin.assinaturas.edit', compact('assinatura', 'planos'));
    }

    public function update(Request $request, Assinatura $assinatura): RedirectResponse
    {
        $assinatura->update($this->validated($request));

        return redirect()
            ->route('admin.assinaturas.index')
            ->with('status', 'Assinatura atualizada.');
    }

    public function destroy(Assinatura $assinatura): RedirectResponse
    {
        $assinatura->delete();

        return redirect()
            ->route('admin.assinaturas.index')
            ->with('status', 'Assinatura removida.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'empresa_nome' => ['required', 'string', 'max:255'],
            'plano_id' => ['nullable', 'integer', 'exists:planos,id'],
            'valor_mensal' => ['required', 'numeric', 'min:0'],
            'proxima_cobranca' => ['required', 'date'],
            'gateway' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(array_keys(Assinatura::statusRotulos()))],
        ]);
    }
}
