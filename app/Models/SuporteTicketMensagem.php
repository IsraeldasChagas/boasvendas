<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuporteTicketMensagem extends Model
{
    protected $table = 'suporte_ticket_mensagens';

    protected $fillable = [
        'suporte_ticket_id',
        'user_id',
        'corpo',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SuporteTicket::class, 'suporte_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
