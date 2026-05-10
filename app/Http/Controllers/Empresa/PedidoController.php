<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Pedido;
use App\Support\WhatsAppPedidoCliente;
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

        return view('empresa.pedidos.show', compact('empresa', 'pedido'));
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

    public function decisaoPendente(Request $request, Pedido $pedido): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $pedido->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $data = $request->validate([
            'decisao' => ['required', 'string', Rule::in(['aceitar', 'recusar'])],
        ]);

        if ($pedido->status !== Pedido::STATUS_PENDENTE_LOJA) {
            return redirect()
                ->route('empresa.pedidos.show', $pedido)
                ->with('warning', 'Este pedido não está aguardando confirmação.');
        }

        if ($data['decisao'] === 'aceitar') {
            $pedido->update(['status' => Pedido::STATUS_RECEBIDO]);

            return $this->finalizePedidoStatusResponse(
                $pedido->fresh(),
                $empresa,
                Pedido::STATUS_RECEBIDO,
                'Pedido aceito. Você pode seguir com o preparo.'
            );
        }

        $pedido->restaurarEstoqueProdutos();
        $pedido->update(['status' => Pedido::STATUS_CANCELADO]);

        return redirect()
            ->route('empresa.pedidos.show', $pedido->fresh())
            ->with('status', 'Pedido recusado. O estoque dos itens foi restaurado.');
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
