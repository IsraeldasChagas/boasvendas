<?php

namespace App\Models;

use App\Enums\Fiscal\FiscalAmbiente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cadastro do emitente fiscal (CNPJ da operação de NF) — tabela {@see $table fiscal_empresas}.
 */
class FiscalEmitente extends Model
{
    protected $table = 'fiscal_empresas';

    protected $fillable = [
        'empresa_id',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'inscricao_estadual',
        'regime_tributario',
        'csc',
        'csc_id',
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
        $d = preg_replace('/\D+/', '', (string) $this->cnpj);

        return strlen($d) === 14
            ? substr($d, 0, 2).'.***.***/****-'.substr($d, -2)
            : (string) $this->cnpj;
    }
}
