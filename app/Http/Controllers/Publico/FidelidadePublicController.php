<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FidelidadePublicController extends Controller
{
    public function show(string $slug): View
    {
        $empresa = $this->empresaPorSlug($slug);

        return view('publico.fidelidade', [
            'slug' => $slug,
            'empresa' => $empresa,
            'programa' => $empresa->fidelidadePrograma,
            'cartao' => null,
            'telefone_digitado' => null,
        ]);
    }

    public function consultar(Request $request, string $slug): View|RedirectResponse
    {
        $empresa = $this->empresaPorSlug($slug);

        $data = $request->validate([
            'telefone' => ['required', 'string', 'min:8', 'max:32'],
        ]);

        $norm = FidelidadeCartao::normalizarTelefone($data['telefone']);
        if (strlen($norm) < 8) {
            return back()->withErrors(['telefone' => 'Informe um telefone válido.'])->withInput();
        }

        $programa = $empresa->fidelidadePrograma;
        $cartao = null;
        if ($programa && $programa->ativo) {
            $cartao = FidelidadeCartao::query()
                ->where('empresa_id', $empresa->id)
                ->where('telefone_normalizado', $norm)
                ->first();
        }

        return view('publico.fidelidade', [
            'slug' => $slug,
            'empresa' => $empresa,
            'programa' => $programa,
            'cartao' => $cartao,
            'telefone_digitado' => $data['telefone'],
        ]);
    }

    private function empresaPorSlug(string $slug): Empresa
    {
        return Empresa::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'suspensa')
            ->with('fidelidadePrograma')
            ->firstOrFail();
    }
}
