<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\VitrineBannerSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VitrineBannerSyncController extends Controller
{
    public function create(): View
    {
        $empresas = Empresa::query()->orderBy('nome')->get(['id', 'nome']);

        return view('admin.empresas.vitrine-banner-sync', compact('empresas'));
    }

    public function store(Request $request, VitrineBannerSyncService $sync): RedirectResponse
    {
        $data = $request->validate([
            'origem_id' => ['required', 'integer', 'exists:empresas,id'],
            'alvos' => ['required', 'string', Rule::in(['todas', 'escolhidas'])],
            'empresa_ids' => ['required_if:alvos,escolhidas', 'array', 'min:1'],
            'empresa_ids.*' => ['integer', 'exists:empresas,id'],
            'substituir_imagens_existentes' => ['nullable', 'boolean'],
        ]);

        $origem = Empresa::query()->findOrFail($data['origem_id']);

        if ($data['alvos'] === 'todas') {
            $targets = Empresa::query()->where('id', '!=', $origem->id)->orderBy('nome')->get();
        } else {
            $ids = collect($data['empresa_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn (int $id) => $id === (int) $origem->id)
                ->values();
            $targets = Empresa::query()->whereIn('id', $ids)->orderBy('nome')->get();
        }

        if ($targets->isEmpty()) {
            return back()->withInput()->with('warning', 'Selecione pelo menos uma empresa destino (diferente da origem).');
        }

        $substituir = $request->boolean('substituir_imagens_existentes');
        $resultado = $sync->sincronizar($origem, $targets, $substituir);

        $msg = sprintf(
            'Sincronização concluída: %d empresas. %d imagens de banner copiadas no total. Categoria em destaque aplicada em %d empresas; em %d não existia categoria ativa com o mesmo nome.',
            $resultado['empresas'],
            $resultado['imagens_total'],
            $resultado['categorias_aplicadas'],
            $resultado['categorias_sem_match']
        );

        return redirect()
            ->route('admin.empresas.vitrine-banner-sync.create')
            ->with('status', $msg);
    }
}
