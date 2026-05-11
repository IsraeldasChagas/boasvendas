<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntregadorPedidoController extends Controller
{
    public function show(string $slug, string $codigo, string $token): View
    {
        $pedido = $this->resolverPedido($slug, $codigo, $token);
        if ($pedido === null) {
            abort(404);
        }

        $pedido->load(['itens', 'empresa']);

        return view('publico.entregador-pedido', [
            'slug' => $slug,
            'empresa' => $pedido->empresa,
            'pedido' => $pedido,
            'token' => $token,
        ]);
    }

    public function registrar(Request $request, string $slug, string $codigo, string $token): RedirectResponse
    {
        $pedido = $this->resolverPedido($slug, $codigo, $token);
        if ($pedido === null) {
            abort(404);
        }

        if (! $pedido->entregadorPodeRegistrarResultado()) {
            return redirect()
                ->route('publico.entregador.show', ['slug' => $slug, 'codigo' => $codigo, 'token' => $token])
                ->with('warning', 'Este pedido já foi finalizado ou não pode ser atualizado por aqui.');
        }

        $data = $request->validate([
            'resultado' => ['required', 'string', 'in:entregue,cancelado,endereco'],
        ]);

        $novoStatus = match ($data['resultado']) {
            'entregue' => Pedido::STATUS_ENTREGUE,
            'cancelado' => Pedido::STATUS_CANCELADO,
            'endereco' => Pedido::STATUS_ENDERECO_NAO_ENCONTRADO,
        };

        $pedido->update(['status' => $novoStatus]);

        return redirect()
            ->route('publico.entregador.show', ['slug' => $slug, 'codigo' => $codigo, 'token' => $token])
            ->with('status', match ($data['resultado']) {
                'entregue' => 'Pedido marcado como entregue. Obrigado!',
                'cancelado' => 'Pedido marcado como cancelado.',
                'endereco' => 'Registrado: endereço não encontrado.',
            });
    }

    private function resolverPedido(string $slug, string $codigo, string $token): ?Pedido
    {
        $codigoNorm = strtoupper(trim($codigo));
        $codigoNorm = ltrim($codigoNorm, '#');
        if (! str_starts_with($codigoNorm, 'BV-')) {
            $codigoNorm = 'BV-'.$codigoNorm;
        }

        $pedido = Pedido::query()
            ->whereHas('empresa', fn ($q) => $q->where('slug', $slug))
            ->where('codigo_publico', $codigoNorm)
            ->first();

        if ($pedido === null) {
            return null;
        }

        if (($pedido->tipo_entrega ?? Pedido::TIPO_ENTREGA_ENTREGA) !== Pedido::TIPO_ENTREGA_ENTREGA) {
            return null;
        }

        $t = $pedido->entregador_token;
        if (! is_string($t) || $t === '' || ! hash_equals($t, $token)) {
            return null;
        }

        return $pedido;
    }
}
