<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EmpresaEntregaFaixaCep;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use App\Services\DeliveryFreteService;
use App\Services\Estoque\EstoqueService;
use App\Enums\Estoque\EstoqueMovimentoTipo;
use App\Support\Cep;
use App\Support\WhatsAppPedidoCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Ponto de Venda interno do painel da empresa: permite ao atendente lançar pedidos
 * recebidos pelo balcão ou pelo WhatsApp/Telefone reaproveitando o motor de frete
 * já existente (DeliveryFreteService / faixas de CEP / Google / OSRM).
 */
class PdvController extends Controller
{
    public function __construct(
        private readonly EstoqueService $estoque,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para usar o PDV.');
        }

        $empresa->loadMissing('plano');

        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'preco', 'estoque', 'categoria_id']);

        $categorias = $produtos
            ->pluck('categoria_id')
            ->filter()
            ->unique()
            ->values();

        $clientes = Schema::hasTable('clientes')
            ? Cliente::query()
                ->where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->orderBy('nome')
                ->get(['id', 'nome', 'telefone', 'email', 'documento'])
            : collect();

        $temBalcao = $this->lojaPermiteRetiradaBalcao($empresa);
        $formasPagamento = $this->formasPagamentoPdv($empresa);

        return view('empresa.pdv.index', [
            'empresa' => $empresa,
            'produtos' => $produtos,
            'categorias' => $categorias,
            'clientes' => $clientes,
            'temBalcao' => $temBalcao,
            'formasPagamento' => $formasPagamento,
        ]);
    }

    public function calcularFrete(Request $request, DeliveryFreteService $delivery): JsonResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return response()->json(['ok' => false, 'message' => 'Empresa não vinculada.'], 422);
        }

        $request->validate([
            'cep' => ['required', 'string', 'max:16'],
            'rua' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:32'],
            'bairro' => ['nullable', 'string', 'max:120'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:2'],
            'subtotal' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $cep8 = Cep::normalizar8((string) $request->input('cep'));
        if ($cep8 === null) {
            return response()->json([
                'ok' => false,
                'message' => 'CEP inválido. Digite 8 dígitos.',
            ]);
        }

        $sub = $request->input('subtotal');
        $subF = ($sub !== null && $sub !== '') ? (float) $sub : null;

        $resumo = $this->calcularTaxaParaCliente($empresa, $delivery, [
            'cep' => $cep8,
            'rua' => (string) $request->input('rua', ''),
            'numero' => (string) $request->input('numero', ''),
            'bairro' => (string) $request->input('bairro', ''),
            'cidade' => (string) $request->input('cidade', ''),
            'estado' => strtoupper((string) $request->input('estado', '')),
        ], $subF);

        return response()->json([
            'ok' => true,
            'taxa' => round((float) $resumo['taxa'], 2),
            'rotulo' => (string) ($resumo['rotulo'] ?? ''),
            'distancia_km' => $resumo['distancia_km'] ?? null,
            'tempo_minutos' => $resumo['tempo_minutos'] ?? null,
            'entrega_bloqueada' => (bool) ($resumo['entrega_bloqueada'] ?? false),
        ]);
    }

    public function store(Request $request, DeliveryFreteService $delivery): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para usar o PDV.');
        }

        $formasPdv = array_keys($this->formasPagamentoPdv($empresa));
        $tiposEntregaPermitidos = [Pedido::TIPO_ENTREGA_ENTREGA];
        if ($this->lojaPermiteRetiradaBalcao($empresa)) {
            $tiposEntregaPermitidos[] = Pedido::TIPO_ENTREGA_BALCAO;
        }

        $canalEntrada = (string) $request->input('canal', '');
        $obrigaCliente = $canalEntrada === Pedido::CANAL_WHATSAPP;

        $trocoRaw = $request->input('pagamento_troco_para');
        if ($trocoRaw === '' || $trocoRaw === null || (is_string($trocoRaw) && trim($trocoRaw) === '')) {
            $request->merge(['pagamento_troco_para' => null]);
        } elseif (is_string($trocoRaw) && str_contains($trocoRaw, ',')) {
            $v = trim($trocoRaw);
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
            $request->merge(['pagamento_troco_para' => is_numeric($v) ? $v : null]);
        }

        $data = $request->validate([
            'canal' => ['required', 'string', Rule::in([Pedido::CANAL_BALCAO, Pedido::CANAL_WHATSAPP])],
            'tipo_entrega' => ['required', 'string', Rule::in($tiposEntregaPermitidos)],
            'cliente_nome' => [$obrigaCliente ? 'required' : 'nullable', 'string', 'max:120'],
            'cliente_telefone' => [$obrigaCliente ? 'required' : 'nullable', 'string', 'max:32'],
            'cep_entrega' => ['nullable', 'string', 'max:16'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'entrega_numero' => ['nullable', 'string', 'max:32'],
            'entrega_bairro' => ['nullable', 'string', 'max:120'],
            'entrega_cidade' => ['nullable', 'string', 'max:120'],
            'entrega_estado' => ['nullable', 'string', 'max:2'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'forma_pagamento' => ['required', 'string', Rule::in($formasPdv)],
            'pagamento_troco_para' => ['nullable', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string', 'max:500'],
            'itens' => ['required', 'array', 'min:1'],
            'itens.*.produto_id' => ['required', 'integer'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.observacao' => ['nullable', 'string', 'max:200'],
            'acao' => ['required', 'string', Rule::in(['finalizar', 'whatsapp_confirmar'])],
        ], [
            'cliente_nome.required' => 'Informe o nome do cliente para pedidos via WhatsApp/Telefone.',
            'cliente_telefone.required' => 'Informe o telefone do cliente para pedidos via WhatsApp/Telefone.',
        ]);

        $produtosIds = collect($data['itens'])->pluck('produto_id')->unique()->values()->all();
        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->whereIn('id', $produtosIds)
            ->get()
            ->keyBy('id');

        $linhas = [];
        $subtotal = 0.0;
        foreach ($data['itens'] as $item) {
            $pid = (int) $item['produto_id'];
            $produto = $produtos->get($pid);
            if (! $produto) {
                throw ValidationException::withMessages([
                    'itens' => "Produto #{$pid} não encontrado ou não pertence a esta loja.",
                ]);
            }
            $qtd = max(1, (int) $item['quantidade']);
            $precoUnit = round((float) $produto->preco, 2);
            $linhaSub = round($precoUnit * $qtd, 2);
            $subtotal += $linhaSub;

            $linhas[] = [
                'produto' => $produto,
                'quantidade' => $qtd,
                'preco_unitario' => $precoUnit,
                'subtotal' => $linhaSub,
                'observacao' => trim((string) ($item['observacao'] ?? '')),
            ];
        }
        $subtotal = round($subtotal, 2);

        $qtdPorProduto = [];
        foreach ($linhas as $l) {
            $pid = (int) $l['produto']->id;
            $qtdPorProduto[$pid] = ($qtdPorProduto[$pid] ?? 0) + (int) $l['quantidade'];
        }
        $estoqueService = app(EstoqueService::class);
        foreach ($qtdPorProduto as $pid => $qtdTotal) {
            $p = $produtos->get($pid);
            if (! $p) {
                continue;
            }
            $estoqueService->garantirDisponivel($p, $qtdTotal);
        }

        $tipoEntrega = $data['tipo_entrega'];
        $taxaVal = 0.0;
        $cepNorm = null;
        $enderecoPedido = '';
        $complementoPedido = $data['complemento'] ?: null;
        $entregaBloqueada = false;

        if ($tipoEntrega === Pedido::TIPO_ENTREGA_BALCAO) {
            $enderecoPedido = 'Retirada no balcão';
        } else {
            $cepNorm = Cep::normalizar8($data['cep_entrega'] ?? '');
            if ($cepNorm === null) {
                throw ValidationException::withMessages([
                    'cep_entrega' => 'Informe um CEP válido (8 dígitos) para calcular a entrega.',
                ]);
            }
            $enderecoTrim = trim((string) ($data['endereco'] ?? ''));
            if ($enderecoTrim === '') {
                throw ValidationException::withMessages([
                    'endereco' => 'Informe o endereço de entrega.',
                ]);
            }
            $enderecoPedido = $this->montarEnderecoEntrega($enderecoTrim, $data);
            if (mb_strlen($enderecoPedido) > 255) {
                $enderecoPedido = mb_substr($enderecoPedido, 0, 255, 'UTF-8');
            }

            $resumo = $this->calcularTaxaParaCliente($empresa, $delivery, [
                'cep' => $cepNorm,
                'rua' => $enderecoTrim,
                'numero' => trim((string) ($data['entrega_numero'] ?? '')),
                'bairro' => trim((string) ($data['entrega_bairro'] ?? '')),
                'cidade' => trim((string) ($data['entrega_cidade'] ?? '')),
                'estado' => strtoupper(trim((string) ($data['entrega_estado'] ?? ''))),
            ], $subtotal);

            $entregaBloqueada = (bool) ($resumo['entrega_bloqueada'] ?? false);
            if ($entregaBloqueada) {
                throw ValidationException::withMessages([
                    'cep_entrega' => 'Endereço fora da área de entrega da loja.',
                ]);
            }
            $taxaVal = round((float) ($resumo['taxa'] ?? 0), 2);
        }

        $total = round($subtotal + $taxaVal, 2);

        $pagamentoTrocoPara = null;
        if ($data['forma_pagamento'] === Pedido::PAGAMENTO_DINHEIRO) {
            $trocoPara = $data['pagamento_troco_para'] ?? null;
            if ($trocoPara !== null && $trocoPara !== '') {
                $v = (float) $trocoPara;
                if ($v < $total) {
                    throw ValidationException::withMessages([
                        'pagamento_troco_para' => 'O valor deve ser maior ou igual ao total (R$ '.number_format($total, 2, ',', '.').').',
                    ]);
                }
                $pagamentoTrocoPara = round($v, 2);
            }
        }

        $canal = $data['canal'];
        $acao = $data['acao'];

        if ($canal === Pedido::CANAL_WHATSAPP && $acao === 'whatsapp_confirmar') {
            $statusInicial = Pedido::STATUS_PENDENTE_LOJA;
        } else {
            $statusInicial = Pedido::STATUS_RECEBIDO;
        }

        // Colunas NOT NULL no banco: garantimos fallback no balcão anônimo.
        $clienteNomeFinal = trim((string) ($data['cliente_nome'] ?? '')) !== ''
            ? $data['cliente_nome']
            : ($canal === Pedido::CANAL_BALCAO ? 'Cliente balcão' : 'Cliente sem nome');
        $clienteTelefoneFinal = trim((string) ($data['cliente_telefone'] ?? '')) !== ''
            ? $data['cliente_telefone']
            : '—';
        $enderecoFinal = trim((string) $enderecoPedido) !== ''
            ? $enderecoPedido
            : 'Retirada no balcão';

        $userId = $request->user()?->id;
        $pedido = DB::transaction(function () use ($empresa, $linhas, $data, $subtotal, $taxaVal, $total, $pagamentoTrocoPara, $tipoEntrega, $cepNorm, $enderecoFinal, $complementoPedido, $statusInicial, $canal, $clienteNomeFinal, $clienteTelefoneFinal, $estoqueService, $userId) {
            $codigo = $this->gerarCodigoPublico();
            $pedido = Pedido::query()->create([
                'empresa_id' => $empresa->id,
                'codigo_publico' => $codigo,
                'canal' => $canal,
                'tipo_entrega' => $tipoEntrega,
                'cliente_nome' => $clienteNomeFinal,
                'cliente_telefone' => $clienteTelefoneFinal,
                'cliente_email' => null,
                'endereco' => $enderecoFinal,
                'complemento' => $complementoPedido,
                'cep_entrega' => $cepNorm,
                'forma_pagamento' => $data['forma_pagamento'],
                'pagamento_troco_para' => $pagamentoTrocoPara,
                'observacoes' => $data['observacoes'] ?: null,
                'status' => $statusInicial,
                'subtotal' => $subtotal,
                'taxa_entrega' => $taxaVal,
                'total' => $total,
            ]);

            foreach ($linhas as $l) {
                $opLinha = null;
                if (($l['observacao'] ?? '') !== '') {
                    $opLinha = ['observacao' => $l['observacao']];
                }

                PedidoItem::query()->create([
                    'pedido_id' => $pedido->id,
                    'produto_id' => $l['produto']->id,
                    'nome_produto' => $l['produto']->nome,
                    'preco_unitario' => $l['preco_unitario'],
                    'quantidade' => $l['quantidade'],
                    'subtotal' => $l['subtotal'],
                    'opcoes_linha' => $opLinha,
                ]);

                $estoqueService->baixar(
                    $l['produto'],
                    (int) $l['quantidade'],
                    EstoqueMovimentoTipo::VendaPdv,
                    $pedido,
                    userId: $userId,
                    comFicha: true,
                );
            }

            return $pedido;
        });

        if ($canal === Pedido::CANAL_WHATSAPP && $acao === 'whatsapp_confirmar') {
            $waUrl = $this->montarLinkWhatsAppResumo($pedido, $empresa);
            $msg = $waUrl !== null
                ? 'Pedido criado em "Aguardando confirmação". Abra o WhatsApp para enviar o resumo ao cliente.'
                : 'Pedido criado em "Aguardando confirmação". Informe um telefone do cliente para gerar o link do WhatsApp.';

            return redirect()
                ->route('empresa.pedidos.show', ['pedido' => $pedido->id])
                ->with('status', $msg)
                ->with('pdv_whatsapp_url', $waUrl);
        }

        return redirect()
            ->route('empresa.pedidos.show', ['pedido' => $pedido->id])
            ->with('status', 'Pedido #'.$pedido->codigo_publico.' criado pelo PDV.');
    }

    /** Aceita os mesmos modos do checkout público (Padrão, Faixas CEP, Google, OSRM) com acréscimo de chuva. */
    private function calcularTaxaParaCliente(Empresa $empresa, DeliveryFreteService $delivery, array $cliente, ?float $subtotal): array
    {
        $modo = $empresa->lojaFreteModoEfetivo();
        $padrao = round($empresa->lojaTaxaEntregaPadraoEfetiva(), 2);

        $resumo = [
            'taxa' => $padrao,
            'rotulo' => 'Taxa padrão da loja',
            'entrega_bloqueada' => false,
        ];

        if ($modo === Empresa::LOJA_FRETE_PADRAO_UNICO) {
            $resumo = ['taxa' => $padrao, 'rotulo' => 'Taxa fixa da loja', 'entrega_bloqueada' => false];
        } elseif ($modo === Empresa::LOJA_FRETE_OSRM_DISTANCIA) {
            $r = $delivery->calcular($empresa, $cliente, $subtotal);
            $resumo = [
                'taxa' => round((float) ($r['taxa_entrega'] ?? $padrao), 2),
                'rotulo' => (string) ($r['rotulo'] ?? 'Frete por rota'),
                'distancia_km' => $r['distancia_km'] ?? null,
                'tempo_minutos' => $r['tempo_minutos'] ?? null,
                'entrega_bloqueada' => (bool) ($r['entrega_bloqueada'] ?? false),
            ];
        } elseif ($modo === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA) {
            $resumo = [
                'taxa' => $padrao,
                'rotulo' => 'Taxa padrão (Google: use a vitrine pública para cálculo automático).',
                'entrega_bloqueada' => false,
            ];
        } else {
            if (Schema::hasTable('empresa_entrega_faixas_cep')) {
                $porFaixa = EmpresaEntregaFaixaCep::taxaParaCep((int) $empresa->id, (string) $cliente['cep']);
                if ($porFaixa !== null) {
                    $resumo = ['taxa' => round($porFaixa, 2), 'rotulo' => 'Faixa de CEP', 'entrega_bloqueada' => false];
                } else {
                    $resumo = ['taxa' => $padrao, 'rotulo' => 'Taxa padrão da loja (sem faixa cadastrada para este CEP)', 'entrega_bloqueada' => false];
                }
            }
        }

        return $empresa->aplicarAcrescimoChuvaNoResumoFrete($resumo);
    }

    private function lojaPermiteRetiradaBalcao(Empresa $empresa): bool
    {
        if (! Schema::hasColumn('empresas', 'loja_permite_retirada_balcao')) {
            return true;
        }

        return (bool) ($empresa->loja_permite_retirada_balcao ?? true);
    }

    /** @return array<string, string> */
    private function formasPagamentoPdv(Empresa $empresa): array
    {
        $base = [
            Pedido::PAGAMENTO_DINHEIRO => 'Dinheiro',
            Pedido::PAGAMENTO_PIX => 'PIX',
            Pedido::PAGAMENTO_CARTAO_CREDITO_MAQUININHA => 'Cartão de crédito (maquininha)',
            Pedido::PAGAMENTO_CARTAO_DEBITO_MAQUININHA => 'Cartão de débito (maquininha)',
            Pedido::PAGAMENTO_ENTREGA => 'Combinar na entrega',
        ];

        if (! $empresa->lojaPixConfiguradaParaCheckout()) {
            unset($base[Pedido::PAGAMENTO_PIX]);
        }

        return $base;
    }

    private function montarEnderecoEntrega(string $logradouro, array $data): string
    {
        $parts = [];
        $log = trim($logradouro);
        if ($log !== '') {
            $parts[] = $log;
        }
        $n = trim((string) ($data['entrega_numero'] ?? ''));
        if ($n !== '') {
            $parts[] = 'nº '.$n;
        }
        $b = trim((string) ($data['entrega_bairro'] ?? ''));
        if ($b !== '') {
            $parts[] = $b;
        }
        $cidade = trim((string) ($data['entrega_cidade'] ?? ''));
        $uf = strtoupper(trim((string) ($data['entrega_estado'] ?? '')));
        if ($cidade !== '' && $uf !== '') {
            $parts[] = $cidade.' — '.$uf;
        } elseif ($cidade !== '') {
            $parts[] = $cidade;
        } elseif ($uf !== '') {
            $parts[] = $uf;
        }

        return implode(', ', $parts);
    }

    private function gerarCodigoPublico(): string
    {
        do {
            $codigo = 'BV-'.strtoupper(Str::random(6));
        } while (Pedido::query()->where('codigo_publico', $codigo)->exists());

        return $codigo;
    }

    private function montarLinkWhatsAppResumo(Pedido $pedido, Empresa $empresa): ?string
    {
        $tel = WhatsAppPedidoCliente::normalizarTelefoneBr($pedido->cliente_telefone ?? null);
        if ($tel === null) {
            return null;
        }

        $pedido->loadMissing('itens');
        $linhas = [];
        $linhas[] = "*Pedido {$pedido->codigo_publico}* — ".($empresa->nome ?? 'sua loja');
        $linhas[] = '';
        $linhas[] = '*Itens:*';
        foreach ($pedido->itens as $it) {
            $linhas[] = '• '.$it->quantidade.'x '.$it->nome_produto.' — R$ '.number_format((float) $it->subtotal, 2, ',', '.');
        }
        $linhas[] = '';
        $linhas[] = 'Subtotal: R$ '.number_format((float) $pedido->subtotal, 2, ',', '.');
        if ((float) $pedido->taxa_entrega > 0) {
            $linhas[] = 'Entrega:  R$ '.number_format((float) $pedido->taxa_entrega, 2, ',', '.');
        }
        $linhas[] = '*Total:    R$ '.number_format((float) $pedido->total, 2, ',', '.').'*';
        $linhas[] = '';
        if ($pedido->tipo_entrega === Pedido::TIPO_ENTREGA_BALCAO) {
            $linhas[] = 'Retirada no balcão';
        } else {
            $linhas[] = 'Entrega: '.$pedido->endereco;
        }
        $linhas[] = 'Pagamento: '.$pedido->rotuloFormaPagamento();
        $linhas[] = '';
        $linhas[] = 'Responda *SIM* para confirmar e começarmos o preparo.';

        $texto = implode("\n", $linhas);

        return 'https://wa.me/'.$tel.'?text='.rawurlencode($texto);
    }
}
