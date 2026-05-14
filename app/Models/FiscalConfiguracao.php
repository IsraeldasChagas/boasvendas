<?php

namespace App\Models;

use App\Enums\Fiscal\FiscalAmbiente;
use App\Enums\Fiscal\FiscalEmissorDriver;
use App\Enums\Fiscal\FiscalModoEmissao;
use App\Enums\Fiscal\FiscalTipoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalConfiguracao extends Model
{
    protected $table = 'fiscal_configuracoes';

    protected $fillable = [
        'empresa_id',
        'modulo_ativo',
        'modo_emissao',
        'tipo_documento',
        'ambiente',
        'emissor_driver_padrao',
        'unidade_id',
    ];

    protected function casts(): array
    {
        return [
            'modulo_ativo' => 'boolean',
            'modo_emissao' => FiscalModoEmissao::class,
            'tipo_documento' => FiscalTipoDocumento::class,
            'ambiente' => FiscalAmbiente::class,
            'emissor_driver_padrao' => FiscalEmissorDriver::class,
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public static function obterOuCriarPadrao(int $empresaId): self
    {
        return static::query()->firstOrCreate(
            ['empresa_id' => $empresaId],
            [
                'modulo_ativo' => false,
                'modo_emissao' => FiscalModoEmissao::NaoEmitir,
                'tipo_documento' => FiscalTipoDocumento::Nfce,
                'ambiente' => FiscalAmbiente::Homologacao,
                'emissor_driver_padrao' => FiscalEmissorDriver::Interno,
            ]
        );
    }
}
