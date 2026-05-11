<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FidelidadeCartao extends Model
{
    protected $table = 'fidelidade_cartoes';

    protected $fillable = [
        'empresa_id',
        'telefone_normalizado',
        'cpf_normalizado',
        'email',
        'cliente_id',
        'selos',
        'total_resgates',
    ];

    protected function casts(): array
    {
        return [
            'selos' => 'integer',
            'total_resgates' => 'integer',
        ];
    }

    public static function normalizarTelefone(?string $raw): string
    {
        return preg_replace('/\D+/', '', (string) $raw);
    }

    /** Onze dígitos ou null. */
    public static function normalizarCpf(?string $raw): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $raw);

        return strlen($d) === 11 ? $d : null;
    }

    public static function cpfValido(string $cpf11): bool
    {
        if (strlen($cpf11) !== 11 || ! ctype_digit($cpf11)) {
            return false;
        }
        if (preg_match('/^(\d)\1{10}$/', $cpf11)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf11[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf11[$t] !== $d) {
                return false;
            }
        }

        return true;
    }

    public function cpfMascarado(): string
    {
        $d = (string) ($this->cpf_normalizado ?? '');
        if (strlen($d) !== 11) {
            return '—';
        }

        return '***.'.$d[3].$d[4].$d[5].'.***-'.substr($d, -2);
    }

    public function emailMascarado(): string
    {
        $e = trim((string) ($this->email ?? ''));
        if ($e === '' || ! str_contains($e, '@')) {
            return '—';
        }
        [$local, $dom] = explode('@', $e, 2);
        $local = (string) $local;
        if (strlen($local) <= 2) {
            return '**@'.$dom;
        }

        return substr($local, 0, 2).'***@'.$dom;
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function telefoneMascarado(): string
    {
        $d = $this->telefone_normalizado;
        if (strlen($d) < 4) {
            return '***';
        }

        return '***'.substr($d, -4);
    }

    public function podeResgatar(FidelidadePrograma $programa): bool
    {
        return $programa->ativo && $this->selos >= $programa->pedidos_meta;
    }
}
