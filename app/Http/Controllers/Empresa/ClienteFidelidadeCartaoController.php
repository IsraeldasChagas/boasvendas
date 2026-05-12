<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\FidelidadeCartao;
use App\Models\FidelidadePontosHistorico;
use App\Support\FidelidadeCartaoWhatsappLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteFidelidadeCartaoController extends Controller
{
    public function show(Request $request, Cliente $cliente): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $cliente->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $cartao = FidelidadeCartao::query()
            ->where('empresa_id', $empresa->id)
            ->where('cliente_id', $cliente->id)
            ->where('status', FidelidadeCartao::STATUS_ATIVO)
            ->first();

        $waUrl = ($cartao && $cartao->codigo_fidelidade)
            ? FidelidadeCartaoWhatsappLink::urlMensagemCartao($cliente, $cartao)
            : null;

        return view('empresa.clientes.cartao-fidelidade', compact('empresa', 'cliente', 'cartao', 'waUrl'));
    }

    public function gerar(Request $request, Cliente $cliente): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $cliente->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $telNorm = FidelidadeCartao::normalizarTelefone($cliente->telefone ?? '');
        if (strlen($telNorm) < 8) {
            return redirect()
                ->route('empresa.clientes.edit', $cliente)
                ->with('warning', 'Cadastre um telefone/WhatsApp no cliente para gerar o cartão fidelidade.');
        }

        $ativoMesmoCliente = FidelidadeCartao::query()
            ->where('empresa_id', $empresa->id)
            ->where('cliente_id', $cliente->id)
            ->where('status', FidelidadeCartao::STATUS_ATIVO)
            ->first();
        if ($ativoMesmoCliente) {
            return redirect()
                ->route('empresa.clientes.cartao-fidelidade.show', $cliente)
                ->with('warning', 'Este cliente já possui um cartão fidelidade ativo.');
        }

        $porTelefone = FidelidadeCartao::query()
            ->where('empresa_id', $empresa->id)
            ->where('telefone_normalizado', $telNorm)
            ->first();

        if ($porTelefone) {
            if ($porTelefone->cliente_id !== null && (int) $porTelefone->cliente_id !== (int) $cliente->id) {
                return redirect()
                    ->route('empresa.clientes.cartao-fidelidade.show', $cliente)
                    ->with('warning', 'Este telefone já está em uso em outro cartão fidelidade.');
            }
            $porTelefone->cliente_id = $cliente->id;
            $porTelefone->status = FidelidadeCartao::STATUS_ATIVO;
            $gerouCodigo = false;
            if ($porTelefone->codigo_fidelidade === null || $porTelefone->codigo_fidelidade === '') {
                $porTelefone->codigo_fidelidade = FidelidadeCartao::gerarCodigoUnico();
                $gerouCodigo = true;
            }
            $porTelefone->save();
            if ($gerouCodigo) {
                $porTelefone->registrarHistorico(FidelidadePontosHistorico::TIPO_GERACAO, 0, 'Cartão fidelidade gerado / vinculado ao cliente');
            }

            return redirect()
                ->route('empresa.clientes.cartao-fidelidade.show', $cliente)
                ->with('status', 'Cartão fidelidade vinculado e ativado para este cliente.');
        }

        $codigo = FidelidadeCartao::gerarCodigoUnico();
        $cartao = FidelidadeCartao::query()->create([
            'empresa_id' => $empresa->id,
            'telefone_normalizado' => $telNorm,
            'cliente_id' => $cliente->id,
            'codigo_fidelidade' => $codigo,
            'selos' => 0,
            'pontos' => 0,
            'total_resgates' => 0,
            'status' => FidelidadeCartao::STATUS_ATIVO,
        ]);
        $cartao->registrarHistorico(FidelidadePontosHistorico::TIPO_GERACAO, 0, 'Cartão fidelidade criado');

        return redirect()
            ->route('empresa.clientes.cartao-fidelidade.show', $cliente)
            ->with('status', 'Cartão fidelidade gerado com sucesso.');
    }
}
