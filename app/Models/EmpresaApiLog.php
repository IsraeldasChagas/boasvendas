<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaApiLog extends Model
{
    public $timestamps = false;

    protected $table = 'empresa_api_logs';

    protected $fillable = [
        'empresa_id',
        'empresa_api_token_id',
        'user_id',
        'method',
        'endpoint',
        'ip',
        'status_http',
        'duration_ms',
        'error',
        'idempotency_key',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'status_http' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(EmpresaApiToken::class, 'empresa_api_token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
