<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\EmpresaEntregaFaixaCep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LojaEntregaFaixaController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        if (! Schema::hasTable('empresa_entrega_faixas_cep')) {
            return redirect()->route('empresa.configuracoes.index')->with('warning', 'Execute as migrations para usar faixas de CEP.');
        }

        $faixas = $empresa->entregaFaixasCep()->get();

        return view('empresa.loja-entrega-faixas-index', compact('empresa', 'faixas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $request->validate([
            'cep_inicio' => ['required', 'string', 'max:12'],
            'cep_fim' => ['required', 'string', 'max:12'],
            'valor_taxa' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'nome_regiao' => ['nullable', 'string', 'max:120'],
        ]);

        $ini = EmpresaEntregaFaixaCep::normalizarLimite($data['cep_inicio']);
        $fim = EmpresaEntregaFaixaCep::normalizarLimite($data['cep_fim']);

        if ($ini === null || $fim === null) {
            return back()->withInput()->withErrors(['cep_inicio' => 'Informe CEPs válidos (8 dígitos).']);
        }

        if ($ini > $fim) {
            return back()->withInput()->withErrors(['cep_fim' => 'O CEP final deve ser maior ou igual ao inicial.']);
        }

        EmpresaEntregaFaixaCep::query()->create([
            'empresa_id' => $empresa->id,
            'cep_inicio' => $ini,
            'cep_fim' => $fim,
            'valor_taxa' => $data['valor_taxa'],
            'nome_regiao' => $data['nome_regiao'] ?: null,
        ]);

        return redirect()->route('empresa.loja-entrega-faixas.index')->with('status', 'Faixa de CEP cadastrada.');
    }

    public function destroy(Request $request, EmpresaEntregaFaixaCep $faixa): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $faixa->empresa_id !== (int) $empresa->id) {
            abort(404);
        }

        $faixa->delete();

        return redirect()->route('empresa.loja-entrega-faixas.index')->with('status', 'Faixa removida.');
    }
}
