<?php

namespace App\Models;

use App\Support\Cep;
use App\Support\GeradorQrCodePix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Empresa extends Model
{
    protected $table = 'empresas';

    /** Usa faixas de CEP cadastradas e, fora delas, a taxa padrão. */
    public const LOJA_FRETE_FAIXAS_CEP = 'faixas_cep';

    /** Sempre a taxa padrão da loja (ou global); ignora faixas de CEP. */
    public const LOJA_FRETE_PADRAO_UNICO = 'padrao_unico';

    /** Frete por distância rodoviária (Google Distance Matrix). */
    public const LOJA_FRETE_GOOGLE_DISTANCIA = 'google_distancia';

    /** Frete por distância (Nominatim + OSRM / OpenStreetMap). */
    public const LOJA_FRETE_OSRM_DISTANCIA = 'osrm_distancia';

    protected $fillable = [
        'nome',
        'loja_aberta',
        'slug',
        'loja_banner_categoria_id',
        'logo',
        'loja_filial_nome',
        'loja_filial_logo',
        'loja_filial_link_url',
        'endereco',
        'cep',
        'whatsapp',
        'instagram_url',
        'facebook_url',
        'loja_taxa_entrega_padrao',
        'loja_permite_retirada_balcao',
        'loja_confirmar_pedidos',
        'loja_impressao_pedido_habilitada',
        'loja_frete_modo',
        'loja_frete_google_rs_por_km',
        'loja_frete_google_taxa_minima',
        'loja_frete_google_km_max',
        'loja_frete_origem_endereco',
        'loja_entrega_lat_origem',
        'loja_entrega_lng_origem',
        'loja_entrega_km_incluso',
        'loja_entrega_valor_km_extra',
        'loja_entrega_gratis_acima_pedido',
        'loja_entrega_chuva_ligado',
        'loja_entrega_chuva_percentual',
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
        'menu_acessos',
        'cliente_desde',
    ];

    protected function casts(): array
    {
        return [
            'cliente_desde' => 'date',
            'menu_acessos' => 'array',
            'loja_taxa_entrega_padrao' => 'decimal:2',
            'loja_frete_google_rs_por_km' => 'decimal:2',
            'loja_frete_google_taxa_minima' => 'decimal:2',
            'loja_frete_google_km_max' => 'decimal:2',
            'loja_entrega_lat_origem' => 'decimal:7',
            'loja_entrega_lng_origem' => 'decimal:7',
            'loja_entrega_km_incluso' => 'decimal:2',
            'loja_entrega_valor_km_extra' => 'decimal:2',
            'loja_entrega_gratis_acima_pedido' => 'decimal:2',
            'loja_entrega_chuva_ligado' => 'boolean',
            'loja_entrega_chuva_percentual' => 'decimal:2',
            'loja_permite_retirada_balcao' => 'boolean',
            'loja_confirmar_pedidos' => 'boolean',
            'loja_impressao_pedido_habilitada' => 'boolean',
            'loja_aberta' => 'boolean',
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

    /**
     * Coluna da FK do banner no cardápio (só após migrate). Falhas ao consultar o schema não devem derrubar o site.
     */
    public static function schemaTemColunaLojaBannerCategoria(): bool
    {
        try {
            return Schema::hasColumn('empresas', 'loja_banner_categoria_id');
        } catch (\Throwable) {
            return false;
        }
    }

    /** Categoria em destaque no topo do cardápio público (banner opcional). */
    public function lojaBannerCategoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'loja_banner_categoria_id');
    }

    /** Imagens extras do banner (upload), após migrate da tabela. */
    public function lojaBannerImagens(): HasMany
    {
        return $this->hasMany(EmpresaLojaBannerImagem::class, 'empresa_id')
            ->orderBy('ordem')
            ->orderBy('id');
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

    public function financeiroDespesasFixas(): HasMany
    {
        return $this->hasMany(FinanceiroDespesaFixa::class, 'empresa_id');
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

    public function entregaFaixasCep(): HasMany
    {
        return $this->hasMany(EmpresaEntregaFaixaCep::class, 'empresa_id')->orderBy('cep_inicio');
    }

    public function entregadoresProprios(): HasMany
    {
        return $this->hasMany(EmpresaEntregador::class, 'empresa_id');
    }

    /** @return array<string, string> valor => rótulo */
    public static function lojaFreteModosRotulos(): array
    {
        $out = [
            self::LOJA_FRETE_FAIXAS_CEP => 'Por CEP (faixas que você cadastra)',
            self::LOJA_FRETE_PADRAO_UNICO => 'Valor único para todas as entregas',
        ];
        if (Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            $out[self::LOJA_FRETE_GOOGLE_DISTANCIA] = 'Por km rodado (Google Maps)';
            $out[self::LOJA_FRETE_OSRM_DISTANCIA] = 'Por km rodado (OpenStreetMap + OSRM)';
        }

        return $out;
    }

    public function lojaFreteModoEfetivo(): string
    {
        if (! Schema::hasColumn('empresas', 'loja_frete_modo')) {
            return self::LOJA_FRETE_FAIXAS_CEP;
        }

        $m = (string) ($this->loja_frete_modo ?? self::LOJA_FRETE_FAIXAS_CEP);
        if ($m === self::LOJA_FRETE_GOOGLE_DISTANCIA && ! Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            return self::LOJA_FRETE_FAIXAS_CEP;
        }
        if ($m === self::LOJA_FRETE_OSRM_DISTANCIA && ! Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            return self::LOJA_FRETE_FAIXAS_CEP;
        }
        $permitidos = [
            self::LOJA_FRETE_FAIXAS_CEP,
            self::LOJA_FRETE_PADRAO_UNICO,
            self::LOJA_FRETE_GOOGLE_DISTANCIA,
            self::LOJA_FRETE_OSRM_DISTANCIA,
        ];

        return in_array($m, $permitidos, true) ? $m : self::LOJA_FRETE_FAIXAS_CEP;
    }

    /** Endereço de saída das entregas: campo da loja, cadastro da empresa ou .env. */
    public function lojaFreteOrigemEnderecoEfetiva(): ?string
    {
        if (Schema::hasColumn('empresas', 'loja_frete_origem_endereco')) {
            $o = trim((string) ($this->loja_frete_origem_endereco ?? ''));
            if ($o !== '') {
                return $o;
            }
        }

        $cepFmt = $this->cepFormatadoParaMaps();

        $e = trim((string) ($this->endereco ?? ''));
        if ($e !== '') {
            if ($cepFmt !== null) {
                return $e.', '.$cepFmt.', Brasil';
            }

            return $e;
        }

        if ($cepFmt !== null) {
            return $cepFmt.', Brasil';
        }

        $g = trim((string) config('services.google_maps.default_origin_address', ''));

        return $g !== '' ? $g : null;
    }

    /** CEP da loja como "00000-000" ou null. */
    public function cepFormatadoParaMaps(): ?string
    {
        if (! Schema::hasColumn('empresas', 'cep')) {
            return null;
        }
        $c = Cep::normalizar8($this->cep ?? null);
        if ($c === null) {
            return null;
        }

        return substr($c, 0, 5).'-'.substr($c, 5);
    }

    /** Valor em R$ por km rodoviário, ou null se não configurado. */
    public function lojaFreteGoogleRsPorKm(): ?float
    {
        if (! Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            return null;
        }
        $v = $this->loja_frete_google_rs_por_km;
        if ($v === null || (float) $v <= 0) {
            return null;
        }

        return round((float) $v, 2);
    }

    public function lojaFreteGoogleTaxaMinima(): ?float
    {
        if (! Schema::hasColumn('empresas', 'loja_frete_google_taxa_minima')) {
            return null;
        }
        $v = $this->loja_frete_google_taxa_minima;
        if ($v === null || (float) $v < 0) {
            return null;
        }

        return round((float) $v, 2);
    }

    /** Distância máxima em km; null = sem limite. */
    public function lojaFreteGoogleKmMax(): ?float
    {
        if (! Schema::hasColumn('empresas', 'loja_frete_google_km_max')) {
            return null;
        }
        $v = $this->loja_frete_google_km_max;
        if ($v === null || (float) $v <= 0) {
            return null;
        }

        return round((float) $v, 2);
    }

    /**
     * Requisitos do frete por Google Maps após salvar (chave no servidor + loja).
     *
     * @return array{api_configurada: bool, rs_por_km: bool, origem: bool, pronto: bool}
     */
    public function lojaFreteGoogleChecklistPronto(): array
    {
        $api = filled(config('services.google_maps.api_key'));
        $rs = $this->lojaFreteGoogleRsPorKm() !== null;
        $origem = $this->lojaFreteOrigemEnderecoEfetiva() !== null;

        return [
            'api_configurada' => $api,
            'rs_por_km' => $rs,
            'origem' => $origem,
            'pronto' => $api && $rs && $origem,
        ];
    }

    /**
     * Requisitos do frete OSRM/OSM (sem chave de API; geocoding + rota no servidor).
     *
     * @return array{rs_por_km: bool, origem: bool, pronto: bool}
     */
    public function lojaFreteOsrmChecklistPronto(): array
    {
        $ua = filled(trim((string) config('services.osm_routing.http_user_agent', '')));
        $coordsSalvas = $this->lojaEntregaCoordenadasOrigemSalvas() !== null;
        $origemTexto = $this->lojaFreteOrigemEnderecoEfetiva() !== null;
        $origem = $coordsSalvas || $origemTexto;

        return [
            'user_agent' => $ua,
            'origem' => $origem,
            'pronto' => $ua && $origem,
        ];
    }

    /** Lat/lng gravados na loja (evita geocode a cada pedido). */
    public function lojaEntregaCoordenadasOrigemSalvas(): ?array
    {
        if (! Schema::hasColumn('empresas', 'loja_entrega_lat_origem')
            || ! Schema::hasColumn('empresas', 'loja_entrega_lng_origem')) {
            return null;
        }
        $lat = $this->loja_entrega_lat_origem;
        $lng = $this->loja_entrega_lng_origem;
        if ($lat === null || $lng === null) {
            return null;
        }

        return ['lat' => (float) $lat, 'lon' => (float) $lng];
    }

    public function lojaEntregaKmInclusoEfetivo(): float
    {
        if (! Schema::hasColumn('empresas', 'loja_entrega_km_incluso')) {
            return 3.0;
        }
        $v = $this->loja_entrega_km_incluso;

        return ($v === null || (float) $v <= 0) ? 3.0 : round((float) $v, 2);
    }

    public function lojaEntregaValorKmExtraEfetivo(): float
    {
        if (! Schema::hasColumn('empresas', 'loja_entrega_valor_km_extra')) {
            return 2.0;
        }
        $v = $this->loja_entrega_valor_km_extra;

        return ($v === null || (float) $v < 0) ? 2.0 : round((float) $v, 2);
    }

    public function lojaEntregaGratisAcimaPedido(): ?float
    {
        if (! Schema::hasColumn('empresas', 'loja_entrega_gratis_acima_pedido')) {
            return null;
        }
        $v = $this->loja_entrega_gratis_acima_pedido;
        if ($v === null || (float) $v <= 0) {
            return null;
        }

        return round((float) $v, 2);
    }

    /** Percentual de acréscimo por chuva (0–100). Zero se coluna ausente ou não configurado. */
    public function lojaEntregaChuvaPercentualEfetivo(): float
    {
        if (! Schema::hasColumn('empresas', 'loja_entrega_chuva_percentual')) {
            return 0.0;
        }
        $v = $this->loja_entrega_chuva_percentual;
        if ($v === null || (float) $v <= 0) {
            return 0.0;
        }

        return min(100.0, round((float) $v, 2));
    }

    /**
     * Multiplica a taxa por (1 + percentual/100) quando "chuva" estiver ligada nas configurações.
     *
     * @param  array{taxa?: float, taxa_entrega?: float, rotulo?: string, entrega_bloqueada?: bool}  $resumo
     * @return array<string, mixed>
     */
    public function aplicarAcrescimoChuvaNoResumoFrete(array $resumo): array
    {
        if (! Schema::hasColumn('empresas', 'loja_entrega_chuva_ligado')) {
            return $resumo;
        }
        if (! empty($resumo['entrega_bloqueada'])) {
            return $resumo;
        }
        $keyTaxa = array_key_exists('taxa_entrega', $resumo) ? 'taxa_entrega' : (array_key_exists('taxa', $resumo) ? 'taxa' : null);
        if ($keyTaxa === null) {
            return $resumo;
        }
        $taxa = round((float) $resumo[$keyTaxa], 2);
        if ($taxa <= 0) {
            return $resumo;
        }
        if (! (bool) ($this->loja_entrega_chuva_ligado ?? false)) {
            return $resumo;
        }
        $pct = $this->lojaEntregaChuvaPercentualEfetivo();
        if ($pct <= 0) {
            return $resumo;
        }
        $nova = round($taxa * (1 + $pct / 100), 2);
        $resumo[$keyTaxa] = $nova;
        $rot = (string) ($resumo['rotulo'] ?? '');
        $pctFmt = fmod($pct, 1.0) < 0.001 ? (string) (int) round($pct) : number_format($pct, 2, ',', '.');
        $suf = ' · +'.$pctFmt.'% (chuva)';
        $resumo['rotulo'] = $rot !== '' ? ($rot.$suf) : ltrim($suf, ' ·');

        return $resumo;
    }

    /** Modos que calculam km rodoviário (Google ou OSRM). */
    public static function lojaFreteModoUsaKmRodoviario(?string $modo): bool
    {
        if ($modo === null || $modo === '') {
            return false;
        }

        return in_array($modo, [self::LOJA_FRETE_GOOGLE_DISTANCIA, self::LOJA_FRETE_OSRM_DISTANCIA], true);
    }

    /** Taxa padrão da loja ou valor global do sistema. */
    public function lojaTaxaEntregaPadraoEfetiva(): float
    {
        if (! Schema::hasColumn('empresas', 'loja_taxa_entrega_padrao')) {
            return (float) config('vendaffacil.taxa_entrega_padrao', 5.99);
        }

        if ($this->loja_taxa_entrega_padrao !== null) {
            return (float) $this->loja_taxa_entrega_padrao;
        }

        return (float) config('vendaffacil.taxa_entrega_padrao', 5.99);
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
            'loja_online' => 'Loja online (vitrine)',
            'fidelidade_programa' => 'Fidelidade: Programa',
            'fidelidade_cartoes' => 'Fidelidade: Cartões',
            'entregas' => 'Entregas',
            'financeiro_visao' => 'Financeiro: Visão geral',
            'financeiro_receber' => 'Financeiro: Contas a receber',
            'financeiro_pagar' => 'Financeiro: Contas a pagar',
            'financeiro_despesas_fixas' => 'Financeiro: Despesas fixas',
            'caixa_visao' => 'Caixa: Visão geral',
            'caixa_fluxo_diario' => 'Caixa: Fluxo do dia',
            'caixa_operacoes' => 'Caixa: Abrir/Movimentos/Fechar',
            'caixa_conferencia' => 'Caixa: Conferência',
            'relatorios' => 'Relatórios',
            've_dashboard' => 'Venda externa: Dashboard',
            've_pontos' => 'Venda externa: Pontos',
            've_remessas' => 'Venda externa: Entregas',
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

        if ($key === 'caixa_fluxo_diario' && in_array('caixa_visao', $libs, true)) {
            return true;
        }
        if (str_starts_with($key, 'fidelidade_') && in_array('fidelidade', $libs, true)) {
            return true;
        }

        if ($key === 'financeiro_despesas_fixas' && (
            in_array('financeiro_pagar', $libs, true)
            || in_array('financeiro_visao', $libs, true)
        )) {
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

    /**
     * Caminho absoluto no disco do arquivo de logo (public/uploads ou storage/app/public legado).
     */
    public function resolveLogoAbsolutePath(): ?string
    {
        if ($this->logo === null || $this->logo === '') {
            return null;
        }

        $rel = ltrim(str_replace('\\', '/', (string) $this->logo), '/');
        if ($rel === '' || Str::contains($rel, '..')) {
            return null;
        }

        $candidates = [
            public_path('uploads/'.$rel),
            public_path($rel),
        ];

        if (Str::startsWith($rel, 'uploads/')) {
            $candidates[] = public_path($rel);
            $candidates[] = public_path('uploads/'.ltrim(Str::after($rel, 'uploads/'), '/'));
        }

        foreach (array_unique(array_filter($candidates)) as $full) {
            if (@is_file($full)) {
                return $full;
            }
        }

        $storage = storage_path('app/public/'.$rel);
        if (@is_file($storage)) {
            return $storage;
        }

        return null;
    }

    /**
     * URL da logo: sempre pela rota que lê o arquivo no disco.
     */
    public function urlLogo(): ?string
    {
        if ($this->resolveLogoAbsolutePath() === null) {
            return null;
        }

        $v = $this->updated_at?->getTimestamp() ?? time();

        return route('publico.empresa_logo', ['empresa' => $this->getKey()], absolute: false).'?v='.$v;
    }

    /** Exibe bloco da filial ao lado da marca na vitrine (nome obrigatório). */
    public function exibirFilialTopo(): bool
    {
        if (! Schema::hasColumn('empresas', 'loja_filial_nome')) {
            return false;
        }

        return trim((string) ($this->loja_filial_nome ?? '')) !== '';
    }

    /**
     * Caminho absoluto da logo da filial (mesma lógica da logo principal).
     */
    public function resolveLogoFilialAbsolutePath(): ?string
    {
        if (! Schema::hasColumn('empresas', 'loja_filial_logo')) {
            return null;
        }
        if ($this->loja_filial_logo === null || $this->loja_filial_logo === '') {
            return null;
        }

        $rel = ltrim(str_replace('\\', '/', (string) $this->loja_filial_logo), '/');
        if ($rel === '' || Str::contains($rel, '..')) {
            return null;
        }

        $candidates = [
            public_path('uploads/'.$rel),
            public_path($rel),
        ];

        if (Str::startsWith($rel, 'uploads/')) {
            $candidates[] = public_path($rel);
            $candidates[] = public_path('uploads/'.ltrim(Str::after($rel, 'uploads/'), '/'));
        }

        foreach (array_unique(array_filter($candidates)) as $full) {
            if (@is_file($full)) {
                return $full;
            }
        }

        $storage = storage_path('app/public/'.$rel);
        if (@is_file($storage)) {
            return $storage;
        }

        return null;
    }

    public function urlLogoFilial(): ?string
    {
        if (! $this->exibirFilialTopo()) {
            return null;
        }
        if ($this->resolveLogoFilialAbsolutePath() === null) {
            return null;
        }

        $v = $this->updated_at?->getTimestamp() ?? time();

        return route('publico.empresa_logo_filial', ['empresa' => $this->getKey()], absolute: false).'?v='.$v;
    }
}
