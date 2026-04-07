<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicoController extends Controller
{
    private function empresaLojaAtiva(string $slug): Empresa
    {
        $empresa = Empresa::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'suspensa')
            ->first();

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
     * @return list<array{produto_id: int, quantidade: int, adicional_ids: list<int>}>
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
     * @param  list<int>  $adicionalIdsOrdenados
     */
    private function fingerprintLinha(int $produtoId, array $adicionalIdsOrdenados): string
    {
        return $produtoId.'|'.implode(',', $adicionalIdsOrdenados);
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
     *   subtotal: float
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
            ->with(['adicionais' => fn ($q) => $q->where('adicionais.ativo', true)])
            ->get()
            ->keyBy('id');

        $novasLinhasSessao = [];
        $linhas = [];
        $idx = 0;

        foreach ($linesRaw as $line) {
            $pid = (int) $line['produto_id'];
            $qty = max(0, (int) $line['quantidade']);
            $idsReq = $this->normalizarIdsAdicionais($line['adicional_ids'] ?? []);
            if ($qty < 1) {
                continue;
            }

            $p = $produtos->get($pid);
            if (! $p) {
                continue;
            }

            $idsPermitidos = $p->permite_adicionais
                ? $p->adicionais->pluck('id')->map(fn ($id) => (int) $id)->all()
                : [];

            $idsOk = array_values(array_filter($idsReq, fn ($id) => in_array($id, $idsPermitidos, true)));

            $opcoes = [];
            $extraUnit = 0.0;
            foreach ($idsOk as $aid) {
                $ad = $p->adicionais->firstWhere('id', $aid);
                if (! $ad) {
                    continue;
                }
                $precoAd = (float) $ad->preco;
                if ($ad->tipo === Adicional::TIPO_ACRESCENTAR) {
                    $extraUnit += $precoAd;
                }
                $opcoes[] = [
                    'id' => (int) $ad->id,
                    'nome' => $ad->nome,
                    'tipo' => $ad->tipo,
                    'preco' => round($precoAd, 2),
                ];
            }

            $base = (float) $p->preco;
            $precoUnit = round($base + $extraUnit, 2);
            $subtotal = round($precoUnit * $qty, 2);

            $novasLinhasSessao[] = [
                'produto_id' => $pid,
                'quantidade' => $qty,
                'adicional_ids' => $idsOk,
            ];

            $linhas[] = [
                'line_index' => $idx,
                'produto' => $p,
                'quantidade' => $qty,
                'adicional_ids' => $idsOk,
                'opcoes' => $opcoes,
                'preco_unitario' => $precoUnit,
                'subtotal' => $subtotal,
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

    private function taxaEntregaValor(Empresa $empresa): float
    {
        return (float) config('vendaffacil.taxa_entrega_padrao', 5.99);
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
            ->withCount(['adicionais as adicionais_loja_count' => function ($q) {
                $q->where('adicionais.ativo', true);
            }]);

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
        ]);

        $qty = $data['quantidade'] ?? 1;

        $p = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', $data['produto_id'])
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with(['adicionais' => fn ($q) => $q->where('adicionais.ativo', true)])
            ->first();

        if (! $p) {
            abort(404, 'Produto não encontrado ou indisponível na loja.');
        }

        $idsReq = $this->normalizarIdsAdicionais($data['adicional_ids'] ?? []);
        if (! $p->permite_adicionais && $idsReq !== []) {
            return back()->with('warning', 'Este produto não permite personalização.');
        }

        $idsPermitidos = $p->adicionais->pluck('id')->map(fn ($id) => (int) $id)->all();
        $idsOk = $this->normalizarIdsAdicionais(array_values(array_intersect($idsPermitidos, $idsReq)));

        if (count($idsOk) !== count($idsReq)) {
            return back()->with('warning', 'Uma das opções escolhidas não é válida para este produto.');
        }

        if ($p->estoque !== null && $p->estoque < $qty) {
            return back()->with('warning', 'Quantidade indisponível em estoque para este produto.');
        }

        $lines = $this->getCarrinhoLines($slug);
        $fp = $this->fingerprintLinha((int) $p->id, $idsOk);
        $found = false;
        foreach ($lines as $i => $line) {
            $lineFp = $this->fingerprintLinha(
                (int) $line['produto_id'],
                $this->normalizarIdsAdicionais($line['adicional_ids'] ?? [])
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
                'adicional_ids' => $idsOk,
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
        $taxa = $this->taxaEntregaValor($empresa);
        $total = round($subtotal + $taxa, 2);

        return view('publico.carrinho', compact('slug', 'empresa', 'linhas', 'subtotal', 'taxa', 'total'));
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
        $taxa = $this->taxaEntregaValor($empresa);
        $total = round($subtotal + $taxa, 2);

        return view('publico.checkout', compact('slug', 'empresa', 'linhas', 'subtotal', 'taxa', 'total'));
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

        $data = $request->validate([
            'cliente_nome' => ['required', 'string', 'max:120'],
            'cliente_telefone' => ['required', 'string', 'max:32'],
            'cliente_email' => ['nullable', 'email', 'max:255'],
            'endereco' => ['required', 'string', 'max:255'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'forma_pagamento' => ['required', 'string', Rule::in(array_keys(Pedido::formasPagamentoRotulos()))],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($linhas as $l) {
            $p = $l['produto'];
            $q = $l['quantidade'];
            if ($p->estoque !== null && $p->estoque < $q) {
                return back()->withInput()->with('warning', 'O produto "'.$p->nome.'" não tem estoque suficiente. Ajuste o carrinho.');
            }
        }

        $subtotal = $this->subtotalCarrinho($linhas);
        $taxa = $this->taxaEntregaValor($empresa);
        $total = round($subtotal + $taxa, 2);

        $pedido = DB::transaction(function () use ($empresa, $linhas, $data, $subtotal, $taxa, $total) {
            $pedido = Pedido::query()->create([
                'empresa_id' => $empresa->id,
                'codigo_publico' => $this->gerarCodigoPublico(),
                'canal' => Pedido::CANAL_LOJA,
                'cliente_nome' => $data['cliente_nome'],
                'cliente_telefone' => $data['cliente_telefone'],
                'cliente_email' => $data['cliente_email'] ?: null,
                'endereco' => $data['endereco'],
                'complemento' => $data['complemento'] ?: null,
                'forma_pagamento' => $data['forma_pagamento'],
                'observacoes' => $data['observacoes'] ?: null,
                'status' => Pedido::STATUS_RECEBIDO,
                'subtotal' => $subtotal,
                'taxa_entrega' => $taxa,
                'total' => $total,
            ]);

            foreach ($linhas as $l) {
                $p = $l['produto'];
                PedidoItem::query()->create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $p->id,
                    'nome_produto' => $p->nome,
                    'preco_unitario' => $l['preco_unitario'],
                    'quantidade' => $l['quantidade'],
                    'subtotal' => $l['subtotal'],
                    'opcoes_linha' => $l['opcoes'] === [] ? null : ['adicionais' => $l['opcoes']],
                ]);

                if ($p->estoque !== null) {
                    $p->decrement('estoque', $l['quantidade']);
                }
            }

            return $pedido;
        });

        $this->setCarrinhoLines($slug, []);

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
