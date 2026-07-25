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
use Illuminate\Support\Facades\Schema;
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
            'padrao_ncm' => ['nullable', 'string', 'max:16'],
            'padrao_origem' => ['nullable', 'integer', 'min:0', 'max:8'],
            'padrao_unidade' => ['nullable', 'string', 'max:8'],
            'padrao_csosn' => ['nullable', 'string', 'max:8'],
            'padrao_cst' => ['nullable', 'string', 'max:8'],
            'padrao_cfop_producao' => ['nullable', 'string', 'max:8'],
            'padrao_cfop_revenda' => ['nullable', 'string', 'max:8'],
        ]);

        $data['modulo_ativo'] = $request->boolean('modulo_ativo');
        if (($data['emissor_driver_padrao'] ?? '') === '') {
            $data['emissor_driver_padrao'] = null;
        }

        if (Schema::hasColumn('fiscal_configuracoes', 'padrao_ncm')) {
            foreach (['padrao_ncm', 'padrao_unidade', 'padrao_csosn', 'padrao_cst', 'padrao_cfop_producao', 'padrao_cfop_revenda'] as $k) {
                if (array_key_exists($k, $data) && trim((string) ($data[$k] ?? '')) === '') {
                    $data[$k] = null;
                }
            }
            if (! array_key_exists('padrao_origem', $data) || $data['padrao_origem'] === null || $data['padrao_origem'] === '') {
                $data['padrao_origem'] = 0;
            }
            $data['padrao_unidade'] = $data['padrao_unidade'] ?: 'UN';
            $data['padrao_csosn'] = $data['padrao_csosn'] ?: '102';
            $data['padrao_cfop_producao'] = $data['padrao_cfop_producao'] ?: '5101';
            $data['padrao_cfop_revenda'] = $data['padrao_cfop_revenda'] ?: '5102';
        } else {
            unset(
                $data['padrao_ncm'],
                $data['padrao_origem'],
                $data['padrao_unidade'],
                $data['padrao_csosn'],
                $data['padrao_cst'],
                $data['padrao_cfop_producao'],
                $data['padrao_cfop_revenda'],
            );
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
