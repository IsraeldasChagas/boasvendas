<?php

namespace App\Models;

use App\Support\GeradorQrCodePix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nome',
        'slug',
        'loja_pix_instrucoes',
        'loja_pix_chave_tipo',
        'loja_pix_chave_valor',
        'loja_pix_banco',
        'loja_pix_copia_cola',
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
            'menu_acessos' => 'array',
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

    public function adicionais(): HasMany
    {
        return $this->hasMany(Adicional::class, 'empresa_id');
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

    public function slugs(): HasMany
    {
        return $this->hasMany(EmpresaSlug::class, 'empresa_id');
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'empresa_modulo')->withTimestamps();
    }

    public static function statusRotulos(): array
    {
        return [
            'ativa' => 'Ativa',
            'trial' => 'Trial',
            'suspensa' => 'Suspensa',
        ];
    }

    /** @return array<string, string> */
    public static function telasMenuEmpresaRotulos(): array
    {
        return [
            'pedidos' => 'Pedidos',
            'produtos' => 'Produtos',
            'categorias' => 'Categorias',
            'adicionais' => 'Adicionais',
            'clientes' => 'Clientes',
            'fidelidade_programa' => 'Fidelidade: Programa',
            'fidelidade_cartoes' => 'Fidelidade: Cartões',
            'entregas' => 'Entregas',
            'financeiro_visao' => 'Financeiro: Visão geral',
            'financeiro_receber' => 'Financeiro: Contas a receber',
            'financeiro_pagar' => 'Financeiro: Contas a pagar',
            'caixa_visao' => 'Caixa: Visão geral',
            'caixa_operacoes' => 'Caixa: Abrir/Movimentos/Fechar',
            'caixa_conferencia' => 'Caixa: Conferência',
            'relatorios' => 'Relatórios',
            've_dashboard' => 'Venda externa: Dashboard',
            've_pontos' => 'Venda externa: Pontos',
            've_remessas' => 'Venda externa: Remessas',
            've_acertos' => 'Venda externa: Acertos',
            've_fiados' => 'Venda externa: Fiados',
            've_relatorios' => 'Venda externa: Relatórios',
            'suporte' => 'Suporte',
            'configuracoes' => 'Configurações',
            'usuarios' => 'Usuários',
        ];
    }

    /** @return list<string> */
    public function telasMenuEmpresaLiberadas(): array
    {
        $raw = $this->menu_acessos;
        if (! is_array($raw)) {
            return [];
        }

        $valid = array_keys(self::telasMenuEmpresaRotulos());

        return collect($raw)
            ->map(fn ($v) => is_string($v) ? $v : '')
            ->filter(fn ($v) => $v !== '' && in_array($v, $valid, true))
            ->unique()
            ->values()
            ->all();
    }

    public function temTelaMenu(string $key): bool
    {
        // Dashboard sempre pode.
        if ($key === 'dashboard') {
            return true;
        }

        $libs = $this->telasMenuEmpresaLiberadas();
        if ($libs === []) {
            // Sem configuração: não bloqueia (compatibilidade).
            return true;
        }

        // Compatibilidade com chave antiga "venda_externa".
        if (str_starts_with($key, 've_') && in_array('venda_externa', $libs, true)) {
            return true;
        }
        // Compatibilidade com chaves antigas (top-level).
        if (str_starts_with($key, 'financeiro_') && in_array('financeiro', $libs, true)) {
            return true;
        }
        if (str_starts_with($key, 'caixa_') && in_array('caixa', $libs, true)) {
            return true;
        }
        if (str_starts_with($key, 'fidelidade_') && in_array('fidelidade', $libs, true)) {
            return true;
        }

        return in_array($key, $libs, true);
    }

    /** PIX habilitado na loja: texto e/ou payload copia e cola. */
    public function lojaPixConfiguradaParaCheckout(): bool
    {
        $i = trim((string) $this->loja_pix_instrucoes);
        $t = trim((string) $this->loja_pix_chave_tipo);
        $v = trim((string) $this->loja_pix_chave_valor);
        $c = trim((string) $this->loja_pix_copia_cola);

        return $i !== '' || (($t !== '' || $v !== '') && $v !== '') || $c !== '';
    }

    public static function pixChaveTiposRotulos(): array
    {
        return [
            'cpf' => 'CPF',
            'cnpj' => 'CNPJ',
            'email' => 'E-mail',
            'telefone' => 'Telefone',
            'aleatoria' => 'Chave aleatória',
        ];
    }

    public function lojaPixChaveRotuloTipo(): string
    {
        $t = (string) $this->loja_pix_chave_tipo;

        return self::pixChaveTiposRotulos()[$t] ?? ($t !== '' ? $t : 'Chave');
    }

    /** @return array<string, string> valor => rótulo para o checkout público */
    public function formasPagamentoLojaPublica(): array
    {
        $opcoes = collect(Pedido::formasPagamentoRotulos())
            ->except([Pedido::PAGAMENTO_CARTAO, Pedido::PAGAMENTO_ENTREGA]);

        if (! $this->lojaPixConfiguradaParaCheckout()) {
            $opcoes = $opcoes->except([Pedido::PAGAMENTO_PIX]);
        }

        return $opcoes->all();
    }

    /** QR em data URI (SVG) a partir do Pix copia e cola; null se não houver payload. */
    public function lojaPixQrCodeDataUri(): ?string
    {
        $p = trim((string) $this->loja_pix_copia_cola);

        return $p !== '' ? GeradorQrCodePix::dataUriSvg($p) : null;
    }
}
