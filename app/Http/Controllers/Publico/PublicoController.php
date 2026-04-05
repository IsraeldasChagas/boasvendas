<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
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
        return Empresa::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'suspensa')
            ->firstOrFail();
    }

    private function carrinhoKey(string $slug): string
    {
        return 'loja_carrinho.'.$slug;
    }

    /** @return array<int, int> produto_id => quantidade */
    private function getCarrinhoBruto(string $slug): array
    {
        $raw = session($this->carrinhoKey($slug), []);

        return is_array($raw) ? $raw : [];
    }

    /** @param  array<int, int>  $items */
    private function setCarrinhoBruto(string $slug, array $items): void
    {
        session([$this->carrinhoKey($slug) => $items]);
    }

    /**
     * @return list<array{produto: Produto, quantidade: int, subtotal: float}>
     */
    private function linhasCarrinho(Empresa $empresa, string $slug): array
    {
        $bruto = $this->getCarrinhoBruto($slug);
        if ($bruto === []) {
            return [];
        }

        $ids = array_keys($bruto);
        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->whereIn('id', $ids)
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->get()
            ->keyBy('id');

        $linhas = [];
        $novoBruto = [];
        foreach ($bruto as $pid => $qty) {
            $pid = (int) $pid;
            $qty = max(0, (int) $qty);
            if ($qty < 1) {
                continue;
            }
            $p = $produtos->get($pid);
            if (! $p) {
                continue;
            }
            $novoBruto[$pid] = $qty;
            $preco = (float) $p->preco;
            $linhas[] = [
                'produto' => $p,
                'quantidade' => $qty,
                'subtotal' => round($preco * $qty, 2),
            ];
        }
        $this->setCarrinhoBruto($slug, $novoBruto);

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
        return (float) config('boasvendas.taxa_entrega_padrao', 5.99);
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
            ->with('categoria');

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

    public function produto(string $slug, string $produto): View
    {
        $empresa = $this->empresaLojaAtiva($slug);

        $produtoModel = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', $produto)
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->with('categoria')
            ->firstOrFail();

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
        ]);

        $qty = $data['quantidade'] ?? 1;

        $p = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('id', $data['produto_id'])
            ->where('ativo', true)
            ->where('visivel_loja', true)
            ->firstOrFail();

        if ($p->estoque !== null && $p->estoque < $qty) {
            return back()->with('warning', 'Quantidade indisponível em estoque para este produto.');
        }

        $bruto = $this->getCarrinhoBruto($slug);
        $pid = (int) $p->id;
        $bruto[$pid] = ($bruto[$pid] ?? 0) + $qty;

        if ($p->estoque !== null && $bruto[$pid] > $p->estoque) {
            return back()->with('warning', 'Não há estoque suficiente para a quantidade desejada.');
        }

        $this->setCarrinhoBruto($slug, $bruto);

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

        $bruto = [];
        foreach ($data['quantidade'] as $pid => $q) {
            $q = (int) $q;
            if ($q > 0) {
                $bruto[(int) $pid] = $q;
            }
        }
        $this->setCarrinhoBruto($slug, $bruto);

        return redirect()
            ->route('publico.carrinho', ['slug' => $slug])
            ->with('status', 'Carrinho atualizado.');
    }

    public function carrinhoRemover(Request $request, string $slug): RedirectResponse
    {
        $this->empresaLojaAtiva($slug);

        $data = $request->validate([
            'produto_id' => ['required', 'integer'],
        ]);

        $bruto = $this->getCarrinhoBruto($slug);
        unset($bruto[(int) $data['produto_id']]);
        $this->setCarrinhoBruto($slug, $bruto);

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
                    'preco_unitario' => $p->preco,
                    'quantidade' => $l['quantidade'],
                    'subtotal' => $l['subtotal'],
                ]);

                if ($p->estoque !== null) {
                    $p->decrement('estoque', $l['quantidade']);
                }
            }

            return $pedido;
        });

        $this->setCarrinhoBruto($slug, []);

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
            ->firstOrFail();

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
