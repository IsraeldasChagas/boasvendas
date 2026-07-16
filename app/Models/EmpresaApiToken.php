<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmpresaApiToken extends Model
{
    protected $table = 'empresa_api_tokens';

    public const ENV_HOMOLOGACAO = 'homologacao';

    public const ENV_PRODUCAO = 'producao';

    protected $fillable = [
        'empresa_id',
        'nome',
        'token_hash',
        'token_prefix',
        'abilities',
        'environment',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'allowed_ips',
        'created_by_user_id',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'allowed_ips' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EmpresaApiLog::class, 'empresa_api_token_id');
    }

    /** @return array{plain: string, model: self} */
    public static function issue(
        Empresa $empresa,
        string $nome,
        array $abilities,
        string $environment = self::ENV_HOMOLOGACAO,
        ?\DateTimeInterface $expiresAt = null,
        ?array $allowedIps = null,
        ?User $createdBy = null,
    ): array {
        $plain = self::generatePlainTextToken();
        $model = new self;
        $model->fill([
            'empresa_id' => $empresa->id,
            'nome' => $nome,
            'token_hash' => self::hashToken($plain),
            'token_prefix' => self::prefixFromPlain($plain),
            'abilities' => array_values($abilities),
            'environment' => $environment,
            'expires_at' => $expiresAt,
            'allowed_ips' => $allowedIps,
            'created_by_user_id' => $createdBy?->id,
        ]);
        $model->save();

        return ['plain' => $plain, 'model' => $model];
    }

    public static function generatePlainTextToken(): string
    {
        $prefix = (string) config('api.token_prefix', 'vf_');

        return $prefix.Str::random(48);
    }

    public static function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public static function prefixFromPlain(string $plain): string
    {
        return Str::substr($plain, 0, 12);
    }

    public static function findValidByPlainToken(string $plain): ?self
    {
        $plain = trim($plain);
        if ($plain === '') {
            return null;
        }

        /** @var self|null $token */
        $token = static::query()
            ->where('token_hash', self::hashToken($plain))
            ->with('empresa')
            ->first();

        if ($token === null || ! $token->isUsable()) {
            return null;
        }

        return $token;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        if ($this->isRevoked() || $this->isExpired()) {
            return false;
        }

        $empresa = $this->empresa;
        if ($empresa === null) {
            return false;
        }

        return $empresa->status !== 'suspensa';
    }

    public function revoke(): void
    {
        if ($this->revoked_at === null) {
            $this->forceFill(['revoked_at' => now()])->save();
        }
    }

    public function touchLastUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    /** @param  list<string>|string  $ability */
    public function tokenCan(string|array $ability): bool
    {
        $abilities = $this->abilities ?? [];
        if ($abilities === [] || in_array('*', $abilities, true)) {
            return true;
        }

        $needed = is_array($ability) ? $ability : [$ability];
        foreach ($needed as $a) {
            if (in_array($a, $abilities, true)) {
                return true;
            }
        }

        return false;
    }

    public function allowsIp(?string $ip): bool
    {
        $list = $this->allowed_ips;
        if (! is_array($list) || $list === []) {
            return true;
        }

        if ($ip === null || $ip === '') {
            return false;
        }

        return in_array($ip, $list, true);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /** @return array<string, string> */
    public static function environmentRotulos(): array
    {
        return config('api.environments', [
            self::ENV_HOMOLOGACAO => 'Homologação',
            self::ENV_PRODUCAO => 'Produção',
        ]);
    }

    public function rotuloEnvironment(): string
    {
        $map = self::environmentRotulos();

        return $map[$this->environment] ?? (string) $this->environment;
    }

    public function estaAtivo(): bool
    {
        return $this->isUsable();
    }
}
