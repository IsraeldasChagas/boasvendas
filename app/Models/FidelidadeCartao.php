<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Apenas dígitos; remove zeros à esquerda do tronco (ex.: 011 9xxxx → 119xxxx)
     * para bater com cadastro e com APIs de WhatsApp em formato internacional.
     */
    public static function normalizarTelefone(?string $raw): string
    {
        $d = preg_replace('/\D+/', '', (string) $raw);
        while (strlen($d) > 11 && str_starts_with($d, '0')) {
            $d = substr($d, 1);
        }

        return $d;
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

    /**
     * Regras de unicidade por loja: CPF e e-mail não podem repetir em outro telefone;
     * o mesmo telefone não pode ficar com outro CPF após o CPF já gravado; cadastro idêntico (telefone + CPF + e-mail) não pode repetir.
     *
     * @param  string  $emailLower  E-mail já em minúsculas e sem espaços nas pontas.
     * @return array{field: string, message: string}|null
     */
    public static function conflitoCadastroFidelidade(int $empresaId, string $telNorm, string $cpf11, string $emailLower, bool $checkout = false): ?array
    {
        $table = (new static)->getTable();

        $fieldTel = $checkout ? 'fidelidade_telefone' : 'cadastro_telefone';
        $fieldTelOutroCpf = $checkout ? 'fidelidade_cpf' : 'cadastro_telefone';
        $fieldCpf = $checkout ? 'fidelidade_cpf' : 'cadastro_cpf';
        $fieldEmail = $checkout ? 'cliente_email' : 'cadastro_email';

        $existenteTel = static::query()
            ->where('empresa_id', $empresaId)
            ->where('telefone_normalizado', $telNorm)
            ->first();

        if (Schema::hasColumn($table, 'cpf_normalizado')
            && $existenteTel
            && $existenteTel->cpf_normalizado
            && $existenteTel->cpf_normalizado !== $cpf11
        ) {
            return [
                'field' => $fieldTelOutroCpf,
                'message' => $checkout
                    ? 'Este telefone já possui cartão fidelidade com outro CPF.'
                    : 'Este telefone já está cadastrado com outro CPF.',
            ];
        }

        if (Schema::hasColumn($table, 'cpf_normalizado')) {
            $cpfEmOutroTelefone = static::query()
                ->where('empresa_id', $empresaId)
                ->where('cpf_normalizado', $cpf11)
                ->where('telefone_normalizado', '!=', $telNorm)
                ->exists();
            if ($cpfEmOutroTelefone) {
                return [
                    'field' => $fieldCpf,
                    'message' => 'Este CPF já está cadastrado em outro telefone.',
                ];
            }
        }

        if (Schema::hasColumn($table, 'email') && $emailLower !== '') {
            $emailEmOutroTelefone = static::query()
                ->where('empresa_id', $empresaId)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->where('email', $emailLower)
                ->where('telefone_normalizado', '!=', $telNorm)
                ->exists();
            if ($emailEmOutroTelefone) {
                return [
                    'field' => $fieldEmail,
                    'message' => 'Este e-mail já está cadastrado em outro telefone.',
                ];
            }
        }

        if (
            $existenteTel
            && Schema::hasColumn($table, 'cpf_normalizado')
            && Schema::hasColumn($table, 'email')
        ) {
            $cpfGravado = (string) ($existenteTel->cpf_normalizado ?? '');
            $emailGravado = strtolower(trim((string) ($existenteTel->email ?? '')));
            if ($cpfGravado === $cpf11 && $emailGravado !== '' && $emailGravado === $emailLower) {
                return [
                    'field' => $fieldTel,
                    'message' => 'Este cadastro (telefone, CPF e e-mail) já existe.',
                ];
            }
        }

        return null;
    }
}
