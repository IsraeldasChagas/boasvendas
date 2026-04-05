<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nome',
        'slug',
        'email_contato',
        'cnpj',
        'plano_id',
        'status',
        'modulos_resumo',
        'cliente_desde',
    ];

    protected function casts(): array
    {
        return [
            'cliente_desde' => 'date',
        ];
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function suporteTickets(): HasMany
    {
        return $this->hasMany(SuporteTicket::class, 'empresa_id');
    }

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class, 'empresa_id');
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class, 'empresa_id');
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'empresa_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'empresa_id');
    }

    public function fidelidadePrograma(): HasOne
    {
        return $this->hasOne(FidelidadePrograma::class, 'empresa_id');
    }

    public function fidelidadeCartoes(): HasMany
    {
        return $this->hasMany(FidelidadeCartao::class, 'empresa_id');
    }

    public function financeiroTitulos(): HasMany
    {
        return $this->hasMany(FinanceiroTitulo::class, 'empresa_id');
    }

    public function caixaTurnos(): HasMany
    {
        return $this->hasMany(CaixaTurno::class, 'empresa_id');
    }

    public function vePontos(): HasMany
    {
        return $this->hasMany(VePonto::class, 'empresa_id');
    }

    public function veRemessas(): HasMany
    {
        return $this->hasMany(VeRemessa::class, 'empresa_id');
    }

    public function veFiados(): HasMany
    {
        return $this->hasMany(VeFiado::class, 'empresa_id');
    }

    public function veAcertos(): HasMany
    {
        return $this->hasMany(VeAcerto::class, 'empresa_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'empresa_id');
    }

    public static function statusRotulos(): array
    {
        return [
            'ativa' => 'Ativa',
            'trial' => 'Trial',
            'suspensa' => 'Suspensa',
        ];
    }
}
