<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaEntregaFaixaCep;
use App\Models\EmpresaSlug;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Support\Cep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicoController extends Controller
{
    private function empresaLojaAtiva(string $slug): Empresa
    {
        $empresa = Empresa::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'suspensa')
            ->first();

        if (! $empresa) {
            $slugRow = EmpresaSlug::query()
                ->where('slug', $slug)
                ->with('empresa')
                ->first();
            $empresa = $slugRow?->empresa;
            if ($empresa && $empresa->status === 'suspensa') {
                $empresa = null;
            }
        }

        if (! $empresa) {
            abort(404, 'Não encontramos esta loja. Verifique o link ou se o estabelecimento ainda está ativo.');
        }

        return $empresa;
    }

    private function carrinhoKey(string $slug): string
    {
        return 'loja_carrinho.'.$slug;
    }

    /**
     * @return list<array{produto_id: int, quantidade: int, adicional_ids: list<int>, retirar_ingrediente_ids: list<int>, observacao: string}>
     */
    private function getCarrinhoLines(string $slug): array
    {
        $raw = session($this->carrinhoKey($slug), []);
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        if (isset($raw[0]) && is_array($raw[0]) && array_key_exists('produto_id', $raw[0])) {
            $out = [];
            foreach ($raw as $line) {
                if (! is_array($line) || ! isset($line['produto_id'])) {
                    continue;
                }
                $out[] = [
                    'produto_id' => (int) $line['produto_id'],
                    'quantidade' => max(0, (int) ($line['quantidade'] ?? 0)),
                    'adicional_ids' => $this->normalizarIdsAdicionais($line['adicional_ids'] ?? []),
                    'retirar_ingrediente_ids' => $this->normalizarIdsAdicionais($line['retirar_ingrediente_ids'] ?? []),
                    'observacao' => $this->normalizarObservacao($line['observacao'] ?? null),
                ];
            }

            return array_values(array_filter($out, fn ($l) => $l['quantidade'] > 0));
        }

        $lines = [];
        foreach ($raw as $pid => $qty) {
            if (! is_numeric($pid)) {
                continue;
            }
            $q = (int) $qty;
            if ($q < 1) {
                continue;
            }
            $lines[] = [
                'produto_id' => (int) $pid,
                'quantidade' => $q,
                'adicional_ids' => [],
                'retirar_ingrediente_ids' => [],
                'observacao' => '',
            ];
        }

        return $lines;
    }

    /**
     * @return list<int>
     */
    private function normalizarIdsAdicionais(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $adicionalIdsAcrescentarOrdenados
     * @param  list<int>  $retirarIngredienteIdsOrdenados
     */
    private function fingerprintLinha(int $produtoId, array $adicionalIdsAcrescentarOrdenados, array $retirarIngredienteIdsOrdenados, string $observacaoNormalizada): string
    {
        return $produtoId.'|a:'.implode(',', $adicionalIdsAcrescentarOrdenados).'|r:'.implode(',', $retirarIngredienteIdsOrdenados).'|'.sha1($observacaoNormalizada);
    }

    private function normalizarObservacao(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $t = trim(strip_tags($text));
        if (function_exists('mb_strlen') && mb_strlen($t) > 500) {
            return mb_substr($t, 0, 500);
        }
        if (strlen($t) > 500) {
            return substr($t, 0, 500);
        }

        return $t;
    }

    private function setCarrinhoLines(string $slug, array $lines): void
    {
        session([$this->carrinhoKey($slug) => array_values($lines)]);
    }

    /**
     * @return list<array{
     *   line_index: int,
     *   produto: Produto,
     *   quantidade: int,
     *   adicional_ids: list<int>,
     *   opcoes: list<array{id:int,nome:string,tipo:string,preco:float}>,
     *   preco_unitario: float,
     *   subtotal: float,
     *   observacao: string
     * }>
     */
    private function linhasCarrinho(Empresa $empresa, string $slug): array
    {
        $linesRaw = $this->getCarrinhoLines($slug);
        if ($linesRaw === []) {
            $this->setCarrinhoLines($slug, []);

            return [];
        }

        $pids = collect($linesRaw)->pluck('produto_id')->unique()->filter()->all();
        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->whereIn('id', $pids)
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with([
                'adicionais' => fn ($q) => $q->where('adicionais.ativo', true),
                'ingredientes' => fn ($q) => $q->orderBy('ordem')->orderBy('nome'),
            ])
            ->get()
            ->keyBy('id');

        $novasLinhasSessao = [];
        $linhas = [];
        $idx = 0;

        foreach ($linesRaw as $line) {
            $pid = (int) $line['produto_id'];
            $qty = max(0, (int) $line['quantidade']);
            $idsReq = $this->normalizarIdsAdicionais($line['adicional_ids'] ?? []);
            $retReq = $this->normalizarIdsAdicionais($line['retirar_ingrediente_ids'] ?? []);
            if ($qty < 1) {
                continue;
            }

            $p = $produtos->get($pid);
            if (! $p) {
                continue;
            }

            $idsPermAcre = $p->permite_adicionais
                ? $p->adicionais->where('tipo', Adicional::TIPO_ACRESCENTAR)->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [];

            $idsOkAcre = array_values(array_intersect($idsPermAcre, $idsReq));

            $idsPermIng = $p->ingredientes->pluck('id')->map(fn ($id) => (int) $id)->all();
            $retOk = array_values(array_intersect($retReq, $idsPermIng));
            sort($retOk);
            $maxR = (int) ($p->max_ingredientes_retirar ?? 0);
            if ($p->ingredientes->isEmpty() || $maxR === 0) {
                $retOk = [];
            } elseif (count($retOk) > $maxR) {
                $retOk = array_slice($retOk, 0, $maxR);
            }

            $obsLinha = $this->normalizarObservacao($line['observacao'] ?? null);

            $opcoes = [];
            $extraUnit = 0.0;
            foreach ($idsOkAcre as $aid) {
                $ad = $p->adicionais->firstWhere('id', $aid);
                if (! $ad || $ad->tipo !== Adicional::TIPO_ACRESCENTAR) {
                    continue;
                }
                $precoAd = (float) $ad->preco;
                $extraUnit += $precoAd;
                $opcoes[] = [
                    'id' => (int) $ad->id,
                    'nome' => $ad->nome,
                    'tipo' => $ad->tipo,
                    'preco' => round($precoAd, 2),
                ];
            }
            foreach ($retOk as $iid) {
                $ing = $p->ingredientes->firstWhere('id', $iid);
                if (! $ing) {
                    continue;
                }
                $opcoes[] = [
                    'id' => (int) $ing->id,
                    'nome' => $ing->nome,
                    'tipo' => 'retirar_ingrediente',
                    'preco' => 0.0,
                ];
            }

            $base = (float) $p->preco;
            $precoUnit = round($base + $extraUnit, 2);
            $subtotal = round($precoUnit * $qty, 2);

            $novasLinhasSessao[] = [
                'produto_id' => $pid,
                'quantidade' => $qty,
                'adicional_ids' => $idsOkAcre,
                'retirar_ingrediente_ids' => $retOk,
                'observacao' => $obsLinha,
            ];

            $linhas[] = [
                'line_index' => $idx,
                'produto' => $p,
                'quantidade' => $qty,
                'adicional_ids' => $idsOkAcre,
                'opcoes' => $opcoes,
                'preco_unitario' => $precoUnit,
                'subtotal' => $subtotal,
                'observacao' => $obsLinha,
            ];
            $idx++;
        }

        $this->setCarrinhoLines($slug, $novasLinhasSessao);

        return $linhas;
    }

    private function subtotalCarrinho(array $linhas): float
    {
        $t = 0.0;
        foreach ($linhas as $l) {
            $t += $l['subtotal'];
        }

        return round($t, 2);
    }

    private function entregaPrefsKey(string $slug): string
    {
        return 'loja_entrega_prefs.'.$slug;
    }

    private function lojaPermiteRetiradaBalcao(Empresa $empresa): bool
    {
        if (! Schema::hasColumn('empresas', 'loja_permite_retirada_balcao')) {
            return true;
        }

        return (bool) $empresa->loja_permite_retirada_balcao;
    }

    /**
     * @return array{modo: string, cep: string}
     */
    private function getEntregaPrefs(string $slug, Empresa $empresa): array
    {
        $default = [
            'modo' => Pedido::TIPO_ENTREGA_ENTREGA,
            'cep' => '',
        ];
        $raw = session($this->entregaPrefsKey($slug), []);
        if (! is_array($raw)) {
            return $default;
        }
        $modoRaw = $raw['modo'] ?? Pedido::TIPO_ENTREGA_ENTREGA;
        $modo = $modoRaw === Pedido::TIPO_ENTREGA_BALCAO && $this->lojaPermiteRetiradaBalcao($empresa)
            ? Pedido::TIPO_ENTREGA_BALCAO
            : Pedido::TIPO_ENTREGA_ENTREGA;
        $cep = isset($raw['cep']) && is_string($raw['cep']) ? preg_replace('/\D+/', '', $raw['cep']) : '';

        return ['modo' => $modo, 'cep' => $cep];
    }

    /**
     * @return array{taxa: float, rotulo: string}
     */
    private function calcularTaxaResumo(Empresa $empresa, string $modo, ?string $cepSoDigitos): array
    {
        if ($modo === Pedido::TIPO_ENTREGA_BALCAO && $this->lojaPermiteRetiradaBalcao($empresa)) {
            return ['taxa' => 0.0, 'rotulo' => 'Retirada no balcão'];
        }

        $cep8 = Cep::normalizar8($cepSoDigitos);
        if ($cep8 === null || $cepSoDigitos === null || $cepSoDigitos === '') {
            return [
                'taxa' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
                'rotulo' => 'Taxa padrão (informe o CEP para usar faixa)',
            ];
        }

        if (Schema::hasTable('empresa_entrega_faixas_cep')) {
            $porFaixa = EmpresaEntregaFaixaCep::taxaParaCep((int) $empresa->id, $cep8);
            if ($porFaixa !== null) {
                return ['taxa' => round($porFaixa, 2), 'rotulo' => 'Faixa de CEP'];
            }
        }

        return [
            'taxa' => round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2),
            'rotulo' => 'Taxa padrão da loja',
        ];
    }

    private function gerarCodigoPublico(): string
    {
        do {
            $codigo = 'BV-'.strtoupper(Str::random(6));
        } while (Pedido::query()->where('codigo_publico', $codigo)->exists());

        return $codigo;
    }

    public function loja(Request $request, string $slug): View
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $empresa->loadMissing('fidelidadePrograma');

        $query = $empresa->produtos()
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with('categoria')
            ->withCount([
                'adicionais as adicionais_acrescimo_count' => function ($q) {
                    $q->where('adicionais.ativo', true)->where('adicionais.tipo', Adicional::TIPO_ACRESCENTAR);
                },
            ])
            ->withCount('ingredientes');

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->integer('categoria_id'));
        }

        $produtos = $query->orderBy('nome')->paginate(24)->withQueryString();

        $categorias = Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->whereHas('produtos', function ($q) {
                $q->where('ativo', true)->where('visivel_loja', true);
            })
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('publico.loja', compact('slug', 'empresa', 'produtos', 'categorias'));
    }

    public function produto(string $slug, string $produto_id): View
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $produtoModel = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', $produto_id)
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with([
                'categoria',
                'adicionais' => fn ($q) => $q->where('adicionais.ativo', true)
                    ->orderBy('adicionais.ordem')
                    ->orderBy('adicionais.nome'),
                'ingredientes' => fn ($q) => $q->orderBy('ordem')->orderBy('nome'),
            ])
            ->first();

        if (! $produtoModel) {
            abort(404, 'Este produto não está disponível na vitrine ou foi removido.');
        }

        return view('publico.produto', [
            'slug' => $slug,
            'empresa' => $empresa,
            'produto' => $produtoModel,
        ]);
    }

    public function carrinhoAdicionar(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'produto_id' => ['required', 'integer'],
            'quantidade' => ['nullable', 'integer', 'min:1', 'max:99'],
            'adicional_ids' => ['nullable', 'array'],
            'adicional_ids.*' => ['integer'],
            'retirar_ingrediente_ids' => ['nullable', 'array'],
            'retirar_ingrediente_ids.*' => ['integer'],
            'observacao' => ['nullable', 'string', 'max:500'],
        ]);

        $qty = $data['quantidade'] ?? 1;
        $obsNorm = $this->normalizarObservacao($data['observacao'] ?? null);

        $p = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', $data['produto_id'])
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with([
                'adicionais' => fn ($q) => $q->where('adicionais.ativo', true),
                'ingredientes' => fn ($q) => $q->orderBy('ordem')->orderBy('nome'),
            ])
            ->first();

        if (! $p) {
            abort(404, 'Produto não encontrado ou indisponível na loja.');
        }

        $idsReq = $this->normalizarIdsAdicionais($data['adicional_ids'] ?? []);
        if (! $p->permite_adicionais && $idsReq !== []) {
            return back()->with('warning', 'Este produto não permite acréscimos opcionais.');
        }

        $idsPermAcre = $p->permite_adicionais
            ? $p->adicionais->where('tipo', Adicional::TIPO_ACRESCENTAR)->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $idsOkAcre = $this->normalizarIdsAdicionais(array_values(array_intersect($idsPermAcre, $idsReq)));

        if (count($idsOkAcre) !== count($idsReq)) {
            return back()->with('warning', 'Uma das opções escolhidas não é válida para este produto.');
        }

        $retReq = $this->normalizarIdsAdicionais($data['retirar_ingrediente_ids'] ?? []);
        $idsPermIng = $p->ingredientes->pluck('id')->map(fn ($id) => (int) $id)->all();
        $retOk = $this->normalizarIdsAdicionais(array_values(array_intersect($idsPermIng, $retReq)));

        if (count($retOk) !== count($retReq)) {
            return back()->with('warning', 'Uma das opções de retirada não é válida para este produto.');
        }

        $maxR = (int) ($p->max_ingredientes_retirar ?? 0);
        if ($p->ingredientes->isEmpty() || $maxR === 0) {
            $retOk = [];
        } elseif (count($retOk) > $maxR) {
            return back()->with('warning', 'Você pode pedir para retirar no máximo '.$maxR.' ingrediente(s) deste item.');
        }

        if ($p->estoque !== null && $p->estoque < $qty) {
            return back()->with('warning', 'Quantidade indisponível em estoque para este produto.');
        }

        $lines = $this->getCarrinhoLines($slug);
        $fp = $this->fingerprintLinha((int) $p->id, $idsOkAcre, $retOk, $obsNorm);
        $found = false;
        foreach ($lines as $i => $line) {
            $lineObs = $this->normalizarObservacao($line['observacao'] ?? null);
            $lineFp = $this->fingerprintLinha(
                (int) $line['produto_id'],
                $this->normalizarIdsAdicionais($line['adicional_ids'] ?? []),
                $this->normalizarIdsAdicionais($line['retirar_ingrediente_ids'] ?? []),
                $lineObs
            );
            if ($lineFp === $fp) {
                $lines[$i]['quantidade'] = (int) $lines[$i]['quantidade'] + $qty;
                $found = true;
                break;
            }
        }

        if (! $found) {
            $lines[] = [
                'produto_id' => (int) $p->id,
                'quantidade' => $qty,
                'adicional_ids' => $idsOkAcre,
                'retirar_ingrediente_ids' => $retOk,
                'observacao' => $obsNorm,
            ];
        }

        $totalMesmoProduto = collect($lines)->where('produto_id', (int) $p->id)->sum('quantidade');
        if ($p->estoque !== null && $totalMesmoProduto > $p->estoque) {
            return back()->with('warning', 'Não há estoque suficiente para a quantidade desejada.');
        }

        $this->setCarrinhoLines($slug, array_values($lines));

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Item adicionado ao carrinho.');
    }

    public function carrinho(string $slug): View
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $linhas = $this->linhasCarrinho($empresa, $slug);
        $subtotal = $this->subtotalCarrinho($linhas);
        $prefs = $this->getEntregaPrefs($slug, $empresa);
        $resumo = $this->calcularTaxaResumo(
            $empresa,
            $prefs['modo'],
            $prefs['cep'] !== '' ? $prefs['cep'] : null
        );
        $taxa = $resumo['taxa'];
        $taxaRotulo = $resumo['rotulo'];
        $total = round($subtotal + $taxa, 2);
        $permiteBalcao = $this->lojaPermiteRetiradaBalcao($empresa);

        return view('publico.carrinho', compact(
            'slug',
            'empresa',
            'linhas',
            'subtotal',
            'taxa',
            'taxaRotulo',
            'total',
            'prefs',
            'permiteBalcao'
        ));
    }

    public function carrinhoEntregaPrefs(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'modo' => ['required', Rule::in([Pedido::TIPO_ENTREGA_ENTREGA, Pedido::TIPO_ENTREGA_BALCAO])],
            'cep' => ['nullable', 'string', 'max:16'],
        ]);

        if ($data['modo'] === Pedido::TIPO_ENTREGA_BALCAO && ! $this->lojaPermiteRetiradaBalcao($empresa)) {
            return back()->with('warning', 'Retirada no balcão não está disponível nesta loja.');
        }

        session([$this->entregaPrefsKey($slug) => [
            'modo' => $data['modo'],
            'cep' => preg_replace('/\D+/', '', (string) ($data['cep'] ?? '')),
        ]]);

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Frete atualizado.');
    }

    public function carrinhoAtualizar(Request $request, string $slug): RedirectResponse
    {
        $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'quantidade' => ['required', 'array'],
            'quantidade.*' => ['integer', 'min:0', 'max:99'],
        ]);

        $lines = $this->getCarrinhoLines($slug);
        foreach ($data['quantidade'] as $idx => $q) {
            $idx = (int) $idx;
            if (! isset($lines[$idx])) {
                continue;
            }
            $lines[$idx]['quantidade'] = (int) $q;
        }
        $lines = array_values(array_filter($lines, fn ($l) => $l['quantidade'] > 0));
        $this->setCarrinhoLines($slug, $lines);

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Carrinho atualizado.');
    }

    public function carrinhoRemover(Request $request, string $slug): RedirectResponse
    {
        $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'line_index' => ['required', 'integer', 'min:0'],
        ]);

        $lines = $this->getCarrinhoLines($slug);
        $i = (int) $data['line_index'];
        if (isset($lines[$i])) {
            array_splice($lines, $i, 1);
        }
        $this->setCarrinhoLines($slug, array_values($lines));

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Item removido.');
    }

    public function checkout(string $slug): View|RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $linhas = $this->linhasCarrinho($empresa, $slug);
        if ($linhas === []) {
            return redirect()
                ->route('publico.carrinho', ['slug' => $slug])
                ->with('warning', 'Seu carrinho está vazio.');
        }

        $subtotal = $this->subtotalCarrinho($linhas);
        $prefs = $this->getEntregaPrefs($slug, $empresa);
        $tipoCheckout = old('tipo_entrega', $prefs['modo']);
        if (! in_array($tipoCheckout, [Pedido::TIPO_ENTREGA_ENTREGA, Pedido::TIPO_ENTREGA_BALCAO], true)) {
            $tipoCheckout = Pedido::TIPO_ENTREGA_ENTREGA;
        }
        if ($tipoCheckout === Pedido::TIPO_ENTREGA_BALCAO && ! $this->lojaPermiteRetiradaBalcao($empresa)) {
            $tipoCheckout = Pedido::TIPO_ENTREGA_ENTREGA;
        }
        $cepDigits = old('cep_entrega') !== null
            ? preg_replace('/\D+/', '', (string) old('cep_entrega'))
            : $prefs['cep'];
        $resumo = $this->calcularTaxaResumo(
            $empresa,
            $tipoCheckout,
            $tipoCheckout === Pedido::TIPO_ENTREGA_ENTREGA && $cepDigits !== '' ? $cepDigits : null
        );
        $taxa = $resumo['taxa'];
        $taxaRotulo = $resumo['rotulo'];
        $total = round($subtotal + $taxa, 2);
        $permiteBalcao = $this->lojaPermiteRetiradaBalcao($empresa);

        $resumoEntregaCep = $this->calcularTaxaResumo(
            $empresa,
            Pedido::TIPO_ENTREGA_ENTREGA,
            $cepDigits !== '' ? $cepDigits : null
        );
        $taxaSeEntrega = $resumoEntregaCep['taxa'];
        $rotuloSeEntrega = $resumoEntregaCep['rotulo'];

        return view('publico.checkout', compact(
            'slug',
            'empresa',
            'linhas',
            'subtotal',
            'taxa',
            'taxaRotulo',
            'total',
            'tipoCheckout',
            'cepDigits',
            'permiteBalcao',
            'taxaSeEntrega',
            'rotuloSeEntrega'
        ));
    }

    public function checkoutFinalizar(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);
        $linhas = $this->linhasCarrinho($empresa, $slug);
        if ($linhas === []) {
            return redirect()
                ->route('publico.carrinho', ['slug' => $slug])
                ->with('warning', 'Seu carrinho está vazio.');
        }

        $subtotalVal = $this->subtotalCarrinho($linhas);

        $formasCheckout = array_keys($empresa->formasPagamentoLojaPublica());

        $tiposEntrega = [Pedido::TIPO_ENTREGA_ENTREGA, Pedido::TIPO_ENTREGA_BALCAO];
        if (! $this->lojaPermiteRetiradaBalcao($empresa)) {
            $tiposEntrega = [Pedido::TIPO_ENTREGA_ENTREGA];
        }

        $data = $request->validate([
            'tipo_entrega' => ['required', Rule::in($tiposEntrega)],
            'cep_entrega' => ['nullable', 'string', 'max:16'],
            'cliente_nome' => ['required', 'string', 'max:120'],
            'cliente_telefone' => ['required', 'string', 'max:32'],
            'cliente_email' => ['nullable', 'email', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'forma_pagamento' => ['required', 'string', Rule::in($formasCheckout)],
            'pagamento_troco_para' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        $tipoEntrega = $data['tipo_entrega'];
        $cepNorm = null;
        $enderecoPedido = '';
        $complementoPedido = $data['complemento'] ?: null;

        if ($tipoEntrega === Pedido::TIPO_ENTREGA_BALCAO) {
            $taxaVal = 0.0;
            $enderecoPedido = 'Retirada no balcão';
        } else {
            $cepNorm = Cep::normalizar8($data['cep_entrega'] ?? '');
            if ($cepNorm === null) {
                return back()
                    ->withInput()
                    ->withErrors(['cep_entrega' => 'Informe um CEP válido (8 dígitos).']);
            }
            if (Schema::hasTable('empresa_entrega_faixas_cep')) {
                $porFaixa = EmpresaEntregaFaixaCep::taxaParaCep((int) $empresa->id, $cepNorm);
                $taxaVal = $porFaixa !== null ? (float) $porFaixa : $empresa->lojaTaxaEntregaPadraoEfetiva();
            } else {
                $taxaVal = $empresa->lojaTaxaEntregaPadraoEfetiva();
            }
            $enderecoTrim = trim((string) ($data['endereco'] ?? ''));
            if ($enderecoTrim === '') {
                return back()
                    ->withInput()
                    ->withErrors(['endereco' => 'Informe o endereço de entrega.']);
            }
            $enderecoPedido = $enderecoTrim;
        }

        $taxaVal = round((float) $taxaVal, 2);
        $totalPedido = round($subtotalVal + $taxaVal, 2);

        $trocoPara = $data['pagamento_troco_para'] ?? null;
        if ($data['forma_pagamento'] === Pedido::PAGAMENTO_DINHEIRO && $trocoPara !== null && $trocoPara !== '') {
            $v = (float) $trocoPara;
            if ($v > 0 && $v < $totalPedido) {
                return back()
                    ->withInput()
                    ->withErrors(['pagamento_troco_para' => 'Informe um valor igual ou maior que o total (R$ '.number_format($totalPedido, 2, ',', '.').') para calcular o troco, ou deixe em branco se for pagamento em valor exato.']);
            }
        }

        $pagamentoTrocoPara = null;
        if ($data['forma_pagamento'] === Pedido::PAGAMENTO_DINHEIRO && $trocoPara !== null && $trocoPara !== '') {
            $pagamentoTrocoPara = round((float) $trocoPara, 2);
        }

        if ($data['forma_pagamento'] === Pedido::PAGAMENTO_PIX && ! $empresa->lojaPixConfiguradaParaCheckout()) {
            return back()
                ->withInput()
                ->withErrors(['forma_pagamento' => 'A loja ainda não configurou o PIX. Escolha outra forma de pagamento.']);
        }

        foreach ($linhas as $l) {
            $p = $l['produto'];
            $q = $l['quantidade'];
            if ($p->estoque !== null && $p->estoque < $q) {
                return back()->withInput()->with('warning', 'O produto "'.$p->nome.'" não tem estoque suficiente. Ajuste o carrinho.');
            }
        }

        $subtotal = $subtotalVal;
        $taxa = $taxaVal;
        $total = $totalPedido;

        $pedido = DB::transaction(function () use ($empresa, $linhas, $data, $subtotal, $taxa, $total, $pagamentoTrocoPara, $tipoEntrega, $cepNorm, $enderecoPedido, $complementoPedido) {
            $pedido = Pedido::query()->create([
                'empresa_id' => $empresa->id,
                'codigo_publico' => $this->gerarCodigoPublico(),
                'canal' => Pedido::CANAL_LOJA,
                'tipo_entrega' => $tipoEntrega,
                'cliente_nome' => $data['cliente_nome'],
                'cliente_telefone' => $data['cliente_telefone'],
                'cliente_email' => $data['cliente_email'] ?: null,
                'endereco' => $enderecoPedido,
                'complemento' => $complementoPedido,
                'cep_entrega' => $cepNorm,
                'forma_pagamento' => $data['forma_pagamento'],
                'pagamento_troco_para' => $pagamentoTrocoPara,
                'observacoes' => $data['observacoes'] ?: null,
                'status' => Pedido::STATUS_RECEBIDO,
                'subtotal' => $subtotal,
                'taxa_entrega' => $taxa,
                'total' => $total,
            ]);

            foreach ($linhas as $l) {
                $p = $l['produto'];
                $opLinha = [];
                if ($l['opcoes'] !== []) {
                    $opLinha['adicionais'] = $l['opcoes'];
                }
                if (($l['observacao'] ?? '') !== '') {
                    $opLinha['observacao'] = $l['observacao'];
                }

                PedidoItem::query()->create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $p->id,
                    'nome_produto' => $p->nome,
                    'preco_unitario' => $l['preco_unitario'],
                    'quantidade' => $l['quantidade'],
                    'subtotal' => $l['subtotal'],
                    'opcoes_linha' => $opLinha === [] ? null : $opLinha,
                ]);

                if ($p->estoque !== null) {
                    $p->decrement('estoque', $l['quantidade']);
                }
            }

            return $pedido;
        });

        $this->setCarrinhoLines($slug, []);
        session([$this->entregaPrefsKey($slug) => [
            'modo' => $tipoEntrega,
            'cep' => $cepNorm !== null ? $cepNorm : '',
        ]]);

        return redirect()
            ->route('publico.pedido.show', ['slug' => $slug, 'codigo' => $pedido->codigo_publico])
            ->with('status', 'Pedido registrado! Guarde o código para acompanhar.');
    }

    public function pedidoPublico(string $slug, string $codigo): View
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $pedido = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo_publico', $codigo)
            ->with('itens')
            ->first();

        if (! $pedido) {
            abort(404, 'Pedido não encontrado nesta loja. Confira o código (ex.: BV-XXXXXX).');
        }

        return view('publico.pedido-show', compact('slug', 'empresa', 'pedido'));
    }

    public function acompanhar(string $slug): View
    {
        $empresa = $this->empresaLojaAtiva($slug);

        return view('publico.acompanhar-pedido', compact('slug', 'empresa'));
    }

    public function acompanharBuscar(Request $request, string $slug): View|RedirectResponse
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:32'],
        ]);

        $codigo = strtoupper(trim($data['codigo']));
        $codigo = ltrim($codigo, '#');
        if (! str_starts_with($codigo, 'BV-')) {
            $codigo = 'BV-'.$codigo;
        }

        $pedido = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo_publico', $codigo)
            ->with('itens')
            ->first();

        if (! $pedido) {
            return back()
                ->withInput()
                ->with('warning', 'Pedido não encontrado. Confira o código (ex.: BV-XXXXXX).');
        }

        return view('publico.pedido-show', [
            'slug' => $slug,
            'empresa' => $empresa,
            'pedido' => $pedido,
        ]);
    }

    /**
     * Serve foto do produto (public/uploads ou storage/app/public legado).
     */
    public function produtoFoto(Produto $produto): BinaryFileResponse
    {
        $full = $produto->resolveFotoAbsolutePath();
        if ($full === null || ! is_file($full)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        if (in_array($ext, ['webp', 'avif'], true)) {
            try {
                $img = null;
                if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($full);
                } elseif ($ext === 'avif' && function_exists('imagecreatefromavif')) {
                    $img = @imagecreatefromavif($full);
                } else {
                    $raw = @file_get_contents($full);
                    if (is_string($raw) && $raw !== '') {
                        $img = @imagecreatefromstring($raw);
                    }
                }

                if ($img !== null && $img !== false) {
                    $w = imagesx($img);
                    $h = imagesy($img);

                    $max = 1400;
                    $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                    $nw = max(1, (int) round($w * $scale));
                    $nh = max(1, (int) round($h * $scale));

                    $dst = imagecreatetruecolor($nw, $nh);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
                    imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

                    ob_start();
                    imagejpeg($dst, null, 85);
                    $jpeg = ob_get_clean();

                    imagedestroy($dst);
                    imagedestroy($img);

                    if (is_string($jpeg) && $jpeg !== '') {
                        return response($jpeg, 200, [
                            'Content-Type' => 'image/jpeg',
                            'Cache-Control' => 'public, max-age=604800',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // fallback abaixo
            }
        }

        return response()->file($full, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * Serve logo da empresa (public/uploads ou storage/app/public legado).
     */
    public function empresaLogo(Empresa $empresa): BinaryFileResponse
    {
        $full = $empresa->resolveLogoAbsolutePath();
        if ($full === null || ! is_file($full)) {
            abort(404);
        }

        return response()->file($full, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    public function legadoCarrinho(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'Abra a loja pelo link do seu restaurante e use o carrinho no menu superior.');
    }

    public function legadoCheckout(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'O checkout fica dentro da página da loja, após adicionar itens ao carrinho.');
    }

    public function legadoProduto(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'Acesse o produto pelo cardápio da loja (link com o nome do estabelecimento).');
    }

    public function legadoAcompanhar(): RedirectResponse
    {
        return redirect()
            ->route('site.home')
            ->with('warning', 'Para acompanhar um pedido, abra a loja onde você comprou e use “Pedido” no menu.');
    }
}
