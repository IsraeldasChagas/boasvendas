<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\FiscalConfiguracao;
use App\Models\FiscalNota;
use App\Models\Pedido;
use App\Services\Fiscal\FiscalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PedidoFiscalController extends Controller
{
    public function __construct(
        private readonly FiscalService $fiscalService,
    ) {}

    public function salvarDados(Request $request, Pedido $pedido): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        $this->autorizaPedido($empresa?->id, $pedido);

        $data = $request->validate([
            'fiscal_quer_cpf_nota' => ['nullable', 'boolean'],
            'fiscal_documento' => ['nullable', 'string', 'max:20'],
            'fiscal_razao_social' => ['nullable', 'string', 'max:180'],
            'fiscal_email' => ['nullable', 'email', 'max:180'],
        ]);

        $data['fiscal_quer_cpf_nota'] = $request->boolean('fiscal_quer_cpf_nota');
        if (! $data['fiscal_quer_cpf_nota']) {
            $data['fiscal_documento'] = null;
            $data['fiscal_razao_social'] = null;
            $data['fiscal_email'] = null;
        }

        $pedido->update($data);

        return redirect()
            ->route('empresa.pedidos.show', $pedido)
            ->with('status', 'Dados fiscais do pedido salvos.');
    }

    public function emitir(Request $request, Pedido $pedido): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        $this->autorizaPedido($empresa?->id, $pedido);
        $config = FiscalConfiguracao::obterOuCriarPadrao($empresa->id);

        $r = $this->fiscalService->iniciarEmissao($pedido, $config);

        return redirect()
            ->route('empresa.pedidos.show', $pedido)
            ->with($r->ok ? 'status' : 'warning', $r->mensagem);
    }

    public function reemitir(Request $request, Pedido $pedido): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        $this->autorizaPedido($empresa?->id, $pedido);
        $config = FiscalConfiguracao::obterOuCriarPadrao($empresa->id);

        $r = $this->fiscalService->reemitir($pedido, $config);

        return redirect()
            ->route('empresa.pedidos.show', $pedido)
            ->with($r->ok ? 'status' : 'warning', $r->mensagem);
    }

    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        $this->autorizaPedido($empresa?->id, $pedido);
        $config = FiscalConfiguracao::obterOuCriarPadrao($empresa->id);

        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $r = $this->fiscalService->cancelarNota($pedido, $config, $data['motivo']);

        return redirect()
            ->route('empresa.pedidos.show', $pedido)
            ->with($r->ok ? 'status' : 'warning', $r->mensagem);
    }

    public function downloadXml(Request $request, Pedido $pedido)
    {
        $empresa = $request->user()->empresa;
        $this->autorizaPedido($empresa?->id, $pedido);

        $nota = FiscalNota::query()->where('pedido_id', $pedido->id)->first();
        if ($nota === null || ! $nota->xml_path || ! Storage::disk('local')->exists($nota->xml_path)) {
            return redirect()->route('empresa.pedidos.show', $pedido)->with('warning', 'XML ainda não disponível para este pedido.');
        }

        return Storage::disk('local')->download($nota->xml_path, 'nf-'.$pedido->codigo_publico.'.xml');
    }

    public function downloadDanfe(Request $request, Pedido $pedido)
    {
        $empresa = $request->user()->empresa;
        $this->autorizaPedido($empresa?->id, $pedido);

        $nota = FiscalNota::query()->where('pedido_id', $pedido->id)->first();
        if ($nota === null || ! $nota->danfe_path || ! Storage::disk('local')->exists($nota->danfe_path)) {
            return redirect()->route('empresa.pedidos.show', $pedido)->with('warning', 'DANFE ainda não disponível para este pedido.');
        }

        return Storage::disk('local')->download($nota->danfe_path, 'danfe-'.$pedido->codigo_publico.'.pdf');
    }

    private function autorizaPedido(?int $empresaId, Pedido $pedido): void
    {
        abort_unless($empresaId && (int) $pedido->empresa_id === (int) $empresaId, 403);
    }
}
