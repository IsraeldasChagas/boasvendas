<?php

namespace App\Models;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalRegimeTributario;
use App\Enums\Fiscal\FiscalTipoPessoa;
use App\Support\Fiscal\DocumentoFiscal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cadastro completo do emitente fiscal PJ ou PF — tabela {@see $table fiscal_empresas}.
 */
class FiscalEmitente extends Model
{
    protected $table = 'fiscal_empresas';

    protected $fillable = [
        'empresa_id',
        'tipo_pessoa',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'cpf',
        'inscricao_estadual',
        'indicador_ie',
        'inscricao_municipal',
        'regime_tributario',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'municipio',
        'codigo_municipio_ibge',
        'uf',
        'telefone',
        'email_fiscal',
        'csc',
        'csc_id',
        'serie_nfce',
        'proximo_numero_nfce',
        'serie_nfe',
        'proximo_numero_nfe',
        'serie_nfse',
        'proximo_numero_nfse',
        'ambiente',
        'certificado_path',
        'certificado_senha',
        'emissor_tipo',
        'api_url',
        'api_token',
        'ativo',
        'unidade_id',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'ambiente' => FiscalAmbiente::class,
            'tipo_pessoa' => FiscalTipoPessoa::class,
            'regime_tributario' => FiscalRegimeTributario::class,
            'proximo_numero_nfce' => 'integer',
            'proximo_numero_nfe' => 'integer',
            'proximo_numero_nfse' => 'integer',
            'certificado_senha' => 'encrypted',
            'api_token' => 'encrypted',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function cnpjMascarado(): string
    {
        return DocumentoFiscal::mascarar($this->cnpj);
    }

    public function documento(): ?string
    {
        return $this->tipo_pessoa === FiscalTipoPessoa::PessoaFisica
            ? $this->cpf
            : $this->cnpj;
    }

    public function documentoMascarado(): string
    {
        return DocumentoFiscal::mascarar($this->documento());
    }

    public function tipoPessoaRotulo(): string
    {
        return $this->tipo_pessoa?->rotulo() ?? 'Pessoa jurídica (CNPJ)';
    }

    public function cadastroFiscalCompleto(): bool
    {
        return collect([
            $this->documento(),
            $this->razao_social,
            $this->regime_tributario?->value,
            $this->indicador_ie,
            $this->cep,
            $this->logradouro,
            $this->numero,
            $this->bairro,
            $this->municipio,
            $this->codigo_municipio_ibge,
            $this->uf,
        ])->every(fn (mixed $valor) => filled($valor));
    }
}
