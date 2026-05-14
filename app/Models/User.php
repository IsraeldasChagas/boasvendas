<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_GESTOR = 'gestor';

    public const ROLE_OPERADOR = 'operador';

    public const ROLE_ENTREGADOR = 'entregador';

    public const ROLE_ATENDENTE = 'atendente';

    public const ROLE_ATENDENTE_CAIXA = 'atendente_caixa';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'empresa_id',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function isAdmin(): bool
    {
        $emails = collect(config('vendaffacil.admin_emails', []))
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter();

        return $emails->contains(strtolower($this->email));
    }

    /**
     * Pode usar o painel master (/admin): lista no .env ou contas demo (mesmo sem empresa_id).
     * Evita login a cair em /empresa quando VENDAFFACIL_ADMIN_EMAILS/cache está errado no servidor.
     */
    public function acessaPainelMaster(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array(strtolower(trim((string) $this->email)), [
            'master@vendaffacil.com.br',
            'admin@vendaffacil.com.br',
        ], true);
    }

    /** @return array<string, string> */
    public static function rolesEquipe(): array
    {
        return [
            self::ROLE_GESTOR => 'Gestor',
            self::ROLE_OPERADOR => 'Operador de caixa',
            self::ROLE_ENTREGADOR => 'Entregador',
            self::ROLE_ATENDENTE => 'Atendente (salão / mesas)',
            self::ROLE_ATENDENTE_CAIXA => 'Atendente caixa (balcão / fechamento)',
        ];
    }

    public function rotuloRoleEquipe(): string
    {
        $role = $this->role ?? '';

        return self::rolesEquipe()[$role] ?? ($role !== '' ? $role : '—');
    }

    /** Gestor ou operador: acesso completo ao painel da empresa (cadastros, financeiro, etc.). */
    public function temPainelEmpresaCompleto(): bool
    {
        return in_array($this->role ?? '', [self::ROLE_GESTOR, self::ROLE_OPERADOR, self::ROLE_ENTREGADOR], true);
    }

    /** Salão ou caixa: só rotas operacionais definidas em podeAcessarRotaEmpresa(). */
    public function temAcessoRestritoAoPainelEmpresa(): bool
    {
        return in_array($this->role ?? '', [self::ROLE_ATENDENTE, self::ROLE_ATENDENTE_CAIXA], true);
    }

    /** Convite e edição de usuários da empresa (não disponível para atendentes). */
    public function podeGerenciarUsuariosEquipe(): bool
    {
        return in_array($this->role ?? '', [self::ROLE_GESTOR, self::ROLE_OPERADOR], true);
    }

    /** Script de pedidos pendentes no layout (somente quem acompanha pedidos de loja). */
    public function deveCarregarPollPedidosPendentesNoPainel(): bool
    {
        return in_array($this->role ?? '', [
            self::ROLE_GESTOR,
            self::ROLE_OPERADOR,
            self::ROLE_ENTREGADOR,
            self::ROLE_ATENDENTE_CAIXA,
        ], true);
    }

    /**
     * Após login no painel da empresa: destino padrão por perfil.
     */
    public function rotaPainelEmpresaPadrao(): string
    {
        if ($this->temAcessoRestritoAoPainelEmpresa()) {
            return 'empresa.mesas.index';
        }

        return 'empresa.dashboard';
    }

    /**
     * Para perfis restritos: lista explícita de rotas nomeadas permitidas (prefixos ou igualdade).
     * Demais rotas empresa.* retornam false.
     */
    public function podeAcessarRotaEmpresa(?string $routeName): bool
    {
        if ($routeName === null || $routeName === '') {
            return true;
        }
        if (! str_starts_with($routeName, 'empresa.')) {
            return true;
        }
        if (! $this->temAcessoRestritoAoPainelEmpresa()) {
            return true;
        }

        if ($routeName === 'empresa.dashboard') {
            return true;
        }

        if ($this->role === self::ROLE_ATENDENTE) {
            $negados = [
                'empresa.mesas.configuracoes',
                'empresa.mesas.relatorios',
                'empresa.mesas.fechamento',
                'empresa.mesas.store',
                'empresa.mesas.update',
                'empresa.mesas.destroy',
                'empresa.comandas.fechar',
                'empresa.comandas.pagamento',
            ];
            foreach ($negados as $prefixo) {
                if (str_starts_with($routeName, $prefixo)) {
                    return false;
                }
            }

            return str_starts_with($routeName, 'empresa.mesas.')
                || str_starts_with($routeName, 'empresa.comandas.');
        }

        if ($this->role === self::ROLE_ATENDENTE_CAIXA) {
            $negados = [
                'empresa.mesas.configuracoes',
                'empresa.mesas.store',
                'empresa.mesas.update',
                'empresa.mesas.destroy',
            ];
            foreach ($negados as $prefixo) {
                if (str_starts_with($routeName, $prefixo)) {
                    return false;
                }
            }

            $liberados = [
                'empresa.mesas.',
                'empresa.comandas.',
                'empresa.pedidos.',
                'empresa.pdv.',
                'empresa.frete-calculadora.',
                'empresa.entregadores.',
                'empresa.caixa.',
            ];
            foreach ($liberados as $prefixo) {
                if (str_starts_with($routeName, $prefixo)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
