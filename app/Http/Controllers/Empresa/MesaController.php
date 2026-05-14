<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\MesaConfiguracao;
use App\Models\User;
use App\Services\Mesas\ComandaService;
use App\Services\Mesas\MesaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MesaController extends Controller
{
    public function __construct(
        private readonly MesaService $mesaService,
        private readonly ComandaService $comandaService,
    ) {}

    public function index(Request $request): View
    {
        $empresaId = (int) $request->user()->empresa_id;
        $mesas = Mesa::query()
            ->daEmpresa($empresaId)
            ->ativas()
            ->with(['comandaAberta.garcom'])
            ->orderBy('numero')
            ->get();

        return view('mesas.index', [
            'mesas' => $mesas,
        ]);
    }

    public function configuracoes(Request $request): View
    {
        $empresaId = (int) $request->user()->empresa_id;
        $config = MesaConfiguracao::obterOuCriarPadrao($empresaId, null);
        $mesas = Mesa::query()->daEmpresa($empresaId)->orderBy('numero')->get();

        return view('mesas.configuracoes', [
            'config' => $config,
            'mesas' => $mesas,
        ]);
    }

    public function configuracoesUpdate(Request $request): RedirectResponse
    {
        $empresaId = (int) $request->user()->empresa_id;
        $data = $request->validate([
            'taxa_servico_padrao_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $config = MesaConfiguracao::obterOuCriarPadrao($empresaId, null);
        $config->taxa_servico_padrao_percent = $data['taxa_servico_padrao_percent'];
        $config->exigir_garcom_abertura = $request->boolean('exigir_garcom_abertura');
        $config->save();

        return redirect()->route('empresa.mesas.configuracoes')->with('success', 'Configurações salvas.');
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = (int) $request->user()->empresa_id;
        $data = $request->validate([
            'numero' => ['required', 'string', 'max:32'],
            'nome' => ['nullable', 'string', 'max:120'],
            'capacidade' => ['nullable', 'integer', 'min:1', 'max:999'],
            'localizacao' => ['nullable', 'string', 'max:120'],
        ]);

        Mesa::query()->create([
            'empresa_id' => $empresaId,
            'unidade_id' => null,
            'numero' => $data['numero'],
            'nome' => $data['nome'] ?? null,
            'capacidade' => $data['capacidade'] ?? 4,
            'localizacao' => $data['localizacao'] ?? null,
            'status' => \App\Enums\Mesas\MesaStatus::Livre,
            'ativo' => true,
        ]);

        return redirect()->route('empresa.mesas.configuracoes')->with('success', 'Mesa cadastrada.');
    }

    public function update(Request $request, Mesa $mesa): RedirectResponse
    {
        $this->authorizeMesa($request, $mesa);
        $data = $request->validate([
            'numero' => ['required', 'string', 'max:32'],
            'nome' => ['nullable', 'string', 'max:120'],
            'capacidade' => ['nullable', 'integer', 'min:1', 'max:999'],
            'localizacao' => ['nullable', 'string', 'max:120'],
            'ativo' => ['required', 'in:0,1'],
        ]);

        $mesa->fill([
            'numero' => $data['numero'],
            'nome' => $data['nome'] ?? null,
            'capacidade' => $data['capacidade'] ?? $mesa->capacidade,
            'localizacao' => $data['localizacao'] ?? null,
            'ativo' => (bool) (int) $data['ativo'],
        ]);
        $mesa->save();

        return redirect()->route('empresa.mesas.configuracoes')->with('success', 'Mesa atualizada.');
    }

    public function destroy(Request $request, Mesa $mesa): RedirectResponse
    {
        $this->authorizeMesa($request, $mesa);
        if ($this->mesaService->comandaAbertaNaMesa($mesa)) {
            return redirect()->route('empresa.mesas.configuracoes')->with('error', 'Não é possível excluir mesa com comanda aberta.');
        }
        $mesa->delete();

        return redirect()->route('empresa.mesas.configuracoes')->with('success', 'Mesa removida.');
    }

    public function abrir(Request $request, Mesa $mesa): RedirectResponse
    {
        $this->authorizeMesa($request, $mesa);
        $data = $request->validate([
            'cliente_nome' => ['nullable', 'string', 'max:160'],
            'cliente_documento' => ['nullable', 'string', 'max:32'],
            'garcom_id' => ['nullable', 'integer', 'exists:users,id'],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $comanda = $this->comandaService->abrirNaMesa(
                $mesa,
                $data['cliente_nome'] ?? null,
                $data['cliente_documento'] ?? null,
                isset($data['garcom_id']) ? (int) $data['garcom_id'] : null,
                $data['observacao'] ?? null,
            );
        } catch (\Throwable $e) {
            return redirect()->route('empresa.mesas.index')->with('error', $e->getMessage());
        }

        return redirect()->route('empresa.comandas.show', $comanda)->with('success', 'Comanda aberta.');
    }

    public function solicitarConta(Request $request, Mesa $mesa): RedirectResponse
    {
        $this->authorizeMesa($request, $mesa);
        try {
            $comanda = $this->mesaService->solicitarConta($mesa);
        } catch (\Throwable $e) {
            return redirect()->route('empresa.mesas.index')->with('error', $e->getMessage());
        }

        if ($request->user()->role === User::ROLE_ATENDENTE) {
            return redirect()
                ->route('empresa.comandas.show', $comanda)
                ->with('success', 'Conta solicitada. O caixa fará o fechamento e o pagamento.');
        }

        return redirect()->route('empresa.mesas.fechamento.show', $comanda)->with('success', 'Conta solicitada. Finalize o pagamento.');
    }

    private function authorizeMesa(Request $request, Mesa $mesa): void
    {
        if ((int) $mesa->empresa_id !== (int) $request->user()->empresa_id) {
            abort(403);
        }
    }
}
