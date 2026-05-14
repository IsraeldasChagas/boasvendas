<?php

namespace App\Http\Controllers\Empresa\Fiscal;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalEmissorDriver;
use App\Enums\Fiscal\FiscalModoEmissao;
use App\Enums\Fiscal\FiscalTipoDocumento;
use App\Http\Controllers\Controller;
use App\Models\FiscalConfiguracao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FiscalConfiguracaoController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $config = FiscalConfiguracao::obterOuCriarPadrao($empresa->id);

        return view('empresa.fiscal.configuracoes', compact('empresa', 'config'));
    }

    public function update(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $data = $request->validate([
            'modulo_ativo' => ['nullable', 'boolean'],
            'modo_emissao' => ['required', Rule::enum(FiscalModoEmissao::class)],
            'tipo_documento' => ['required', Rule::enum(FiscalTipoDocumento::class)],
            'ambiente' => ['required', Rule::enum(FiscalAmbiente::class)],
            'emissor_driver_padrao' => ['nullable', 'string', Rule::in(array_column(FiscalEmissorDriver::cases(), 'value'))],
        ]);

        $data['modulo_ativo'] = $request->boolean('modulo_ativo');
        if (($data['emissor_driver_padrao'] ?? '') === '') {
            $data['emissor_driver_padrao'] = null;
        }

        FiscalConfiguracao::query()->updateOrCreate(
            ['empresa_id' => $empresa->id],
            $data + ['empresa_id' => $empresa->id]
        );

        return redirect()
            ->route('empresa.fiscal.configuracoes.edit')
            ->with('status', 'Configurações fiscais salvas.');
    }
}
