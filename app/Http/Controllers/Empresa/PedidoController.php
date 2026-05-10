<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Support\WhatsAppPedidoCliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if (($pedido->tipo_entrega ?? Pedido::TIPO_ENTREGA_ENTREGA) === Pedido::TIPO_ENTREGA_ENTREGA) {
            $pedido->ensureEntregadorToken();
        }

        return view('empresa.pedidos.show', compact('empresa', 'pedido'));
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

        $redirect = redirect()
            ->route('empresa.pedidos.show', $pedido)
            ->with('status', 'Status do pedido atualizado.');

        $waUrl = WhatsAppPedidoCliente::urlAvisoStatus($pedido->fresh(), $empresa, $novoStatus);
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
