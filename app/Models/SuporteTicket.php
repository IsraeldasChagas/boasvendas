<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuporteTicket extends Model
{
    protected $table = 'suporte_tickets';

    protected $fillable = [
        'empresa_id',
        'assunto',
        'descricao',
        'prioridade',
        'status',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(SuporteTicketMensagem::class, 'suporte_ticket_id')->orderBy('created_at');
    }

    /** @return array<string, string> */
    public static function prioridades(): array
    {
        return [
            'baixa' => 'Baixa',
            'media' => 'Média',
            'alta' => 'Alta',
        ];
    }

    /** @return array<string, string> */
    public static function statusRotulos(): array
    {
        return [
            'aberto' => 'Aberto',
            'aguardando' => 'Aguardando',
            'em_andamento' => 'Em andamento',
            'resolvido' => 'Resolvido',
            'fechado' => 'Fechado',
        ];
    }
}
