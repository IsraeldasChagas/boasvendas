<?php

namespace App\Http\Controllers\Empresa\Fiscal;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalEmissorDriver;
use App\Enums\Fiscal\FiscalRegimeTributario;
use App\Enums\Fiscal\FiscalTipoPessoa;
use App\Http\Controllers\Controller;
use App\Models\FiscalEmitente;
use App\Support\Fiscal\DocumentoFiscal;
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

        $emitente = new FiscalEmitente([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => FiscalTipoPessoa::PessoaJuridica,
            'ativo' => true,
            'ambiente' => FiscalAmbiente::Homologacao,
            'emissor_tipo' => FiscalEmissorDriver::Interno->value,
            'indicador_ie' => 'nao_contribuinte',
            'proximo_numero_nfce' => 1,
            'proximo_numero_nfe' => 1,
            'proximo_numero_nfse' => 1,
        ]);

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
        $empresaId = (int) $request->user()->empresa_id;
        $tipoPessoa = $request->string('tipo_pessoa')->toString();
        $documentoValido = static function (string $attribute, mixed $value, \Closure $fail) use ($tipoPessoa): void {
            $valido = $tipoPessoa === FiscalTipoPessoa::PessoaFisica->value
                ? DocumentoFiscal::cpfValido((string) $value)
                : DocumentoFiscal::cnpjValido((string) $value);

            if (! $valido) {
                $fail($tipoPessoa === FiscalTipoPessoa::PessoaFisica->value
                    ? 'Informe um CPF válido.'
                    : 'Informe um CNPJ válido.');
            }
        };

        $cnpjUnico = Rule::unique('fiscal_empresas', 'cnpj')
            ->where(fn ($query) => $query->where('empresa_id', $empresaId))
            ->ignore($existente?->id);
        $cpfUnico = Rule::unique('fiscal_empresas', 'cpf')
            ->where(fn ($query) => $query->where('empresa_id', $empresaId))
            ->ignore($existente?->id);

        $data = $request->validate([
            'tipo_pessoa' => ['required', Rule::enum(FiscalTipoPessoa::class)],
            'razao_social' => ['required', 'string', 'max:180'],
            'nome_fantasia' => ['nullable', 'string', 'max:180'],
            'cnpj' => ['exclude_unless:tipo_pessoa,pj', 'required_if:tipo_pessoa,pj', 'string', 'max:18', $cnpjUnico, $documentoValido],
            'cpf' => ['exclude_unless:tipo_pessoa,pf', 'required_if:tipo_pessoa,pf', 'string', 'max:14', $cpfUnico, $documentoValido],
            'indicador_ie' => ['required', Rule::in(['contribuinte', 'isento', 'nao_contribuinte'])],
            'inscricao_estadual' => ['nullable', 'required_if:indicador_ie,contribuinte', 'string', 'max:32'],
            'inscricao_municipal' => ['nullable', 'string', 'max:32'],
            'regime_tributario' => ['required', Rule::enum(FiscalRegimeTributario::class)],
            'cep' => ['required', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/'],
            'logradouro' => ['required', 'string', 'max:180'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:80'],
            'bairro' => ['required', 'string', 'max:80'],
            'municipio' => ['required', 'string', 'max:100'],
            'codigo_municipio_ibge' => ['required', 'digits:7'],
            'uf' => ['required', 'string', 'size:2', Rule::in([
                'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS',
                'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC',
                'SP', 'SE', 'TO',
            ])],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email_fiscal' => ['nullable', 'email:rfc', 'max:180'],
            'csc' => ['nullable', 'string', 'max:120'],
            'csc_id' => ['nullable', 'string', 'max:32'],
            'serie_nfce' => ['nullable', 'string', 'max:8'],
            'proximo_numero_nfce' => ['required', 'integer', 'min:1'],
            'serie_nfe' => ['nullable', 'string', 'max:8'],
            'proximo_numero_nfe' => ['required', 'integer', 'min:1'],
            'serie_nfse' => ['nullable', 'string', 'max:8'],
            'proximo_numero_nfse' => ['required', 'integer', 'min:1'],
            'ambiente' => ['required', Rule::enum(FiscalAmbiente::class)],
            'certificado_path' => ['nullable', 'string', 'max:512'],
            'certificado_senha' => ['nullable', 'string', 'max:500'],
            'emissor_tipo' => ['required', Rule::in(array_column(FiscalEmissorDriver::cases(), 'value'))],
            'api_url' => ['nullable', 'url:http,https', 'max:512'],
            'api_token' => ['nullable', 'string', 'max:2000'],
            'ativo' => ['nullable', 'boolean'],
        ]) + ['ativo' => $request->boolean('ativo')];

        $data['cnpj'] = $tipoPessoa === FiscalTipoPessoa::PessoaJuridica->value
            ? DocumentoFiscal::somenteDigitos($data['cnpj'] ?? null)
            : null;
        $data['cpf'] = $tipoPessoa === FiscalTipoPessoa::PessoaFisica->value
            ? DocumentoFiscal::somenteDigitos($data['cpf'] ?? null)
            : null;
        if ($tipoPessoa === FiscalTipoPessoa::PessoaFisica->value) {
            $data['nome_fantasia'] = null;
        }
        $data['cep'] = DocumentoFiscal::somenteDigitos($data['cep']);
        $data['codigo_municipio_ibge'] = DocumentoFiscal::somenteDigitos($data['codigo_municipio_ibge']);
        $data['uf'] = strtoupper($data['uf']);
        if ($data['indicador_ie'] !== 'contribuinte') {
            $data['inscricao_estadual'] = $data['indicador_ie'] === 'isento' ? 'ISENTO' : null;
        }

        return $data;
    }

    private function autoriza(?int $empresaId, FiscalEmitente $emitente): void
    {
        abort_unless($empresaId && (int) $emitente->empresa_id === (int) $empresaId, 403);
    }
}
