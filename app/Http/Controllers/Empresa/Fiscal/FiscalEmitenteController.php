<?php

namespace App\Http\Controllers\Empresa\Fiscal;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalEmissorDriver;
use App\Http\Controllers\Controller;
use App\Models\FiscalEmitente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FiscalEmitenteController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $emitentes = FiscalEmitente::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('ativo')
            ->orderBy('razao_social')
            ->get();

        return view('empresa.fiscal.emitentes.index', compact('empresa', 'emitentes'));
    }

    public function create(Request $request): View
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $emitente = new FiscalEmitente(['empresa_id' => $empresa->id, 'ativo' => true, 'ambiente' => FiscalAmbiente::Homologacao, 'emissor_tipo' => FiscalEmissorDriver::Interno->value]);

        return view('empresa.fiscal.emitentes.form', compact('empresa', 'emitente'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $data = $this->validated($request);
        $data['empresa_id'] = $empresa->id;
        FiscalEmitente::query()->create($data);

        return redirect()->route('empresa.fiscal.emitentes.index')->with('status', 'Emitente cadastrado.');
    }

    public function edit(Request $request, FiscalEmitente $emitente): View
    {
        $empresa = $request->user()->empresa;
        $this->autoriza($empresa?->id, $emitente);

        return view('empresa.fiscal.emitentes.form', compact('empresa', 'emitente'));
    }

    public function update(Request $request, FiscalEmitente $emitente): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        $this->autoriza($empresa?->id, $emitente);

        $data = $this->validated($request, $emitente);
        if (($data['certificado_senha'] ?? '') === '') {
            unset($data['certificado_senha']);
        }
        if (($data['api_token'] ?? '') === '') {
            unset($data['api_token']);
        }
        $emitente->update($data);

        return redirect()->route('empresa.fiscal.emitentes.index')->with('status', 'Emitente atualizado.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?FiscalEmitente $existente = null): array
    {
        return $request->validate([
            'razao_social' => ['required', 'string', 'max:180'],
            'nome_fantasia' => ['nullable', 'string', 'max:180'],
            'cnpj' => ['required', 'string', 'max:18'],
            'inscricao_estadual' => ['nullable', 'string', 'max:32'],
            'regime_tributario' => ['nullable', 'string', 'max:32'],
            'csc' => ['nullable', 'string', 'max:120'],
            'csc_id' => ['nullable', 'string', 'max:32'],
            'ambiente' => ['required', Rule::enum(FiscalAmbiente::class)],
            'certificado_path' => ['nullable', 'string', 'max:512'],
            'certificado_senha' => ['nullable', 'string', 'max:500'],
            'emissor_tipo' => ['required', Rule::in(array_column(FiscalEmissorDriver::cases(), 'value'))],
            'api_url' => ['nullable', 'string', 'max:512'],
            'api_token' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['nullable', 'boolean'],
        ]) + ['ativo' => $request->boolean('ativo')];
    }

    private function autoriza(?int $empresaId, FiscalEmitente $emitente): void
    {
        abort_unless($empresaId && (int) $emitente->empresa_id === (int) $empresaId, 403);
    }
}
