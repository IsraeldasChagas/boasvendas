<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaEntregador;
use App\Models\Pedido;
use App\Support\CupomPedidoCliente;
use App\Support\WhatsAppPedidoCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para ver os pedidos.');
        }

        $query = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('created_at');

        $st = $request->input('status');
        if (is_string($st) && array_key_exists($st, Pedido::statusRotulos())) {
            $query->where('status', $st);
        }

        $pedidos = $query->paginate(25)->withQueryString();

        return view('empresa.pedidos.index', compact('empresa', 'pedidos'));
    }

    public function show(Request $request, Pedido $pedido): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $pedido->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $pedido->load(['itens.produto']);

        if (($pedido->tipo_entrega ?? Pedido::TIPO_ENTREGA_ENTREGA) === Pedido::TIPO_ENTREGA_ENTREGA
            && $pedido->status !== Pedido::STATUS_PENDENTE_LOJA) {
            $pedido->ensureEntregadorToken();
        }

        $cupomWhatsUrl = CupomPedidoCliente::urlWhatsAppCupom($pedido, $empresa);

        $entregadoresParaPedido = collect();
        if (Schema::hasTable('empresa_entregadores')
            && ($pedido->tipo_entrega ?? Pedido::TIPO_ENTREGA_ENTREGA) === Pedido::TIPO_ENTREGA_ENTREGA) {
            $entregadoresParaPedido = EmpresaEntregador::query()
                ->where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->orderBy('ordem')
                ->orderBy('nome')
                ->get();
        }

        $fiscalConfig = null;
        $fiscalNota = null;
        if (Schema::hasTable('fiscal_configuracoes')) {
            $fiscalConfig = \App\Models\FiscalConfiguracao::obterOuCriarPadrao($empresa->id);
        }
        if (Schema::hasTable('fiscal_notas')) {
            $fiscalNota = \App\Models\FiscalNota::query()->where('pedido_id', $pedido->id)->first();
        }

        return view('empresa.pedidos.show', compact('empresa', 'pedido', 'cupomWhatsUrl', 'entregadoresParaPedido', 'fiscalConfig', 'fiscalNota'));
    }

    public function imprimir(Request $request, Pedido $pedido): View
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $pedido->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        if (Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada')
            && ! ($empresa->loja_impressao_pedido_habilitada ?? true)) {
            abort(403, 'Impressão do pedido está desativada nas configurações.');
        }

        $pedido->load(['itens']);

        return view('empresa.pedidos.imprimir', compact('empresa', 'pedido'));
    }

    public function pollPendentes(Request $request): JsonResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return response()->json(['enabled' => false, 'pedidos' => []]);
        }

        if (! $this->empresaUsaConfirmacaoPedidoPendente($empresa)) {
            return response()->json(['enabled' => false, 'pedidos' => []]);
        }

        $pedidos = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', Pedido::STATUS_PENDENTE_LOJA)
            ->with(['itens'])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'enabled' => true,
            'pedidos' => $pedidos->map(fn (Pedido $p) => $this->serializePedidoPendentePoll($p))->values()->all(),
        ]);
    }

    public function decisaoPendente(Request $request, Pedido $pedido): RedirectResponse|JsonResponse
    {
        $empresa = $request->user()->empresa;
        $expectsJson = $request->expectsJson();

        if (! $empresa || (int) $pedido->empresa_id !== (int) $empresa->id) {
            return $expectsJson
                ? response()->json(['ok' => false, 'message' => 'Sem permissão para este pedido.'], 403)
                : abort(403);
        }

        $data = $request->validate([
            'decisao' => ['required', 'string', Rule::in(['aceitar', 'recusar'])],
        ]);

        if ($pedido->status !== Pedido::STATUS_PENDENTE_LOJA) {
            if ($expectsJson) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Este pedido não está aguardando confirmação.',
                ], 422);
            }

            return redirect()
                ->route('empresa.pedidos.show', $pedido)
                ->with('warning', 'Este pedido não está aguardando confirmação.');
        }

        if ($data['decisao'] === 'aceitar') {
            $pedido->update(['status' => Pedido::STATUS_RECEBIDO]);
            $fresh = $pedido->fresh(['itens']);

            if ($expectsJson) {
                return response()->json([
                    'ok' => true,
                    'mensagem' => 'Pedido aceito. Você pode seguir com o preparo.',
                    'proximo' => $this->proximoPedidoPendentePollPayload($empresa),
                    'whatsapp_aviso_url' => WhatsAppPedidoCliente::urlAvisoStatus($fresh, $empresa, Pedido::STATUS_RECEBIDO),
                ]);
            }

            return $this->finalizePedidoStatusResponse(
                $fresh,
                $empresa,
                Pedido::STATUS_RECEBIDO,
                'Pedido aceito. Você pode seguir com o preparo.'
            );
        }

        $pedido->restaurarEstoqueProdutos();
        $pedido->update(['status' => Pedido::STATUS_CANCELADO]);
        $fresh = $pedido->fresh(['itens']);

        if ($expectsJson) {
            return response()->json([
                'ok' => true,
                'mensagem' => 'Pedido recusado. O estoque dos itens foi restaurado.',
                'proximo' => $this->proximoPedidoPendentePollPayload($empresa),
                'whatsapp_aviso_url' => WhatsAppPedidoCliente::urlAvisoStatus($fresh, $empresa, Pedido::STATUS_CANCELADO),
            ]);
        }

        return redirect()
            ->route('empresa.pedidos.show', $fresh)
            ->with('status', 'Pedido recusado. O estoque dos itens foi restaurado.');
    }

    private function empresaUsaConfirmacaoPedidoPendente(Empresa $empresa): bool
    {
        if (! $empresa->temTelaMenu('loja_online')) {
            return false;
        }

        if (! Schema::hasColumn('empresas', 'loja_confirmar_pedidos')) {
            return false;
        }

        return (bool) ($empresa->loja_confirmar_pedidos ?? false);
    }

    /** @return array<string, mixed>|null */
    private function proximoPedidoPendentePollPayload(Empresa $empresa): ?array
    {
        $proximo = Pedido::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', Pedido::STATUS_PENDENTE_LOJA)
            ->with(['itens'])
            ->orderBy('created_at')
            ->first();

        return $proximo ? $this->serializePedidoPendentePoll($proximo) : null;
    }

    /** @return array<string, mixed> */
    private function serializePedidoPendentePoll(Pedido $pedido): array
    {
        $pedido->loadMissing('itens');

        return [
            'id' => $pedido->id,
            'codigo_publico' => $pedido->codigo_publico,
            'cliente_nome' => $pedido->cliente_nome,
            'total_fmt' => 'R$ '.number_format((float) $pedido->total, 2, ',', '.'),
            'tipo_entrega' => $pedido->rotuloTipoEntrega(),
            'created_at' => $pedido->created_at->format('d/m/Y H:i'),
            'pendente_post_url' => route('empresa.pedidos.pendente', $pedido),
            'show_url' => route('empresa.pedidos.show', $pedido),
            'itens' => $pedido->itens->map(fn ($it) => [
                'nome' => $it->nome_produto,
                'qtd' => (int) $it->quantidade,
            ])->values()->all(),
        ];
    }

    public function updateStatus(Request $request, Pedido $pedido): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $pedido->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(Pedido::statusRotulos()))],
        ]);

        if ($pedido->status === Pedido::STATUS_PENDENTE_LOJA) {
            return redirect()
                ->route('empresa.pedidos.show', $pedido)
                ->with('warning', 'Este pedido ainda não foi aceito pela loja. Use Aceitar ou Recusar no painel acima.');
        }

        $novoStatus = $data['status'];
        $statusAnterior = $pedido->status;

        if ($statusAnterior === $novoStatus) {
            return redirect()
                ->route('empresa.pedidos.show', $pedido)
                ->with('warning', 'O status já estava assim. Escolha outro para atualizar.');
        }

        $pedido->update(['status' => $novoStatus]);

        return $this->finalizePedidoStatusResponse($pedido->fresh(), $empresa, $novoStatus);
    }

    private function finalizePedidoStatusResponse(Pedido $pedido, Empresa $empresa, string $novoStatus, ?string $mensagemSucesso = null): RedirectResponse
    {
        $redirect = redirect()
            ->route('empresa.pedidos.show', $pedido)
            ->with('status', $mensagemSucesso ?? 'Status do pedido atualizado.');

        $waUrl = WhatsAppPedidoCliente::urlAvisoStatus($pedido, $empresa, $novoStatus);
        if ($waUrl !== null) {
            return $redirect->with('vf_whatsapp_aviso_cliente', $waUrl);
        }

        if (($pedido->canal ?? Pedido::CANAL_LOJA) === Pedido::CANAL_LOJA) {
            return $redirect->with(
                'vf_whatsapp_indisponivel',
                'Não foi possível gerar o link do WhatsApp. Confira se o telefone do cliente tem DDD e número corretos.'
            );
        }

        return $redirect;
    }
}
