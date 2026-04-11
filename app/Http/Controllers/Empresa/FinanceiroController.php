<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\CaixaMovimento;
use App\Models\CaixaTurno;
use App\Models\Empresa;
use App\Models\FinanceiroDespesaFixa;
use App\Models\FinanceiroTitulo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceiroController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa para ver o financeiro.');
        }

        $hoje = Carbon::today();

        $aReceber = (float) FinanceiroTitulo::query()
            ->where('empresa_id', $empresa->id)
            ->where('tipo', FinanceiroTitulo::TIPO_RECEBER)
            ->where('status', FinanceiroTitulo::STATUS_ABERTO)
            ->sum('valor');

        $aPagar = (float) FinanceiroTitulo::query()
            ->where('empresa_id', $empresa->id)
            ->where('tipo', FinanceiroTitulo::TIPO_PAGAR)
            ->where('status', FinanceiroTitulo::STATUS_ABERTO)
            ->sum('valor');

        $recebidoHoje = (float) FinanceiroTitulo::query()
            ->where('empresa_id', $empresa->id)
            ->where('tipo', FinanceiroTitulo::TIPO_RECEBER)
            ->where('status', FinanceiroTitulo::STATUS_PAGO)
            ->whereDate('pago_em', $hoje)
            ->sum('valor');

        $pagoHoje = (float) FinanceiroTitulo::query()
            ->where('empresa_id', $empresa->id)
            ->where('tipo', FinanceiroTitulo::TIPO_PAGAR)
            ->where('status', FinanceiroTitulo::STATUS_PAGO)
            ->whereDate('pago_em', $hoje)
            ->sum('valor');

        $saldoDia = $recebidoHoje - $pagoHoje;

        $chartEntrada = [];
        $chartSaida = [];
        $chartLabels = [];
        $start = Carbon::now()->subMonths(5)->startOfMonth();
        for ($i = 0; $i < 6; $i++) {
            $m = $start->copy()->addMonths($i);
            $chartLabels[] = $m->translatedFormat('M/y');
            $ini = $m->copy()->startOfMonth();
            $fim = $m->copy()->endOfMonth();

            $chartEntrada[] = (float) FinanceiroTitulo::query()
                ->where('empresa_id', $empresa->id)
                ->where('tipo', FinanceiroTitulo::TIPO_RECEBER)
                ->where('status', FinanceiroTitulo::STATUS_PAGO)
                ->whereBetween('pago_em', [$ini->toDateString(), $fim->toDateString()])
                ->sum('valor');

            $chartSaida[] = (float) FinanceiroTitulo::query()
                ->where('empresa_id', $empresa->id)
                ->where('tipo', FinanceiroTitulo::TIPO_PAGAR)
                ->where('status', FinanceiroTitulo::STATUS_PAGO)
                ->whereBetween('pago_em', [$ini->toDateString(), $fim->toDateString()])
                ->sum('valor');
        }

        $chartMax = max(1, ...$chartEntrada, ...$chartSaida);

        $totalDespesasFixasMensal = 0.0;
        if (Schema::hasTable('financeiro_despesas_fixas')) {
            $totalDespesasFixasMensal = (float) FinanceiroDespesaFixa::query()
                ->where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->sum('valor_mensal');
        }

        return view('empresa.financeiro.index', compact(
            'empresa',
            'aReceber',
            'aPagar',
            'saldoDia',
            'chartEntrada',
            'chartSaida',
            'chartLabels',
            'chartMax',
            'totalDespesasFixasMensal'
        ));
    }

    public function despesasFixasIndex(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $despesas = FinanceiroDespesaFixa::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome')
            ->limit(500)
            ->get();

        $totalMensal = (float) $despesas->where('ativo', true)->sum('valor_mensal');

        return view('empresa.financeiro.despesas-fixas-index', compact('empresa', 'despesas', 'totalMensal'));
    }

    public function despesasFixasCreate(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.financeiro.despesas-fixa-form', [
            'empresa' => $empresa,
            'despesa' => new FinanceiroDespesaFixa(['ativo' => true, 'valor_mensal' => '0']),
        ]);
    }

    public function despesasFixasStore(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        FinanceiroDespesaFixa::query()->create(
            $this->validatedDespesaFixa($request) + ['empresa_id' => $empresa->id]
        );

        return redirect()->route('empresa.financeiro.despesas-fixas.index')->with('status', 'Despesa fixa cadastrada.');
    }

    public function despesasFixasPagar(Request $request, FinanceiroDespesaFixa $financeiroDespesaFixa): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $financeiroDespesaFixa->empresa_id !== (int) $empresa->id) {
            abort(404);
        }

        $data = $request->validate([
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'pago_em' => ['required', 'date'],
            'repetir_proximo' => ['required', Rule::in(['0', '1', 0, 1])],
        ]);

        $repetir = in_array($data['repetir_proximo'], [1, '1'], true);

        $pagoEm = Carbon::parse($data['pago_em'])->startOfDay();

        $descricao = 'Despesa fixa: '.$financeiroDespesaFixa->nome;
        if ($financeiroDespesaFixa->categoria) {
            $descricao .= ' ('.$financeiroDespesaFixa->categoria.')';
        }

        $lancouCaixa = false;

        DB::transaction(function () use ($request, $empresa, $financeiroDespesaFixa, $data, $pagoEm, $descricao, $repetir, &$lancouCaixa) {
            FinanceiroTitulo::query()->create([
                'empresa_id' => $empresa->id,
                'tipo' => FinanceiroTitulo::TIPO_PAGAR,
                'contraparte' => $financeiroDespesaFixa->nome,
                'descricao' => $descricao,
                'valor' => $data['valor'],
                'vencimento' => $pagoEm->toDateString(),
                'status' => FinanceiroTitulo::STATUS_PAGO,
                'pago_em' => $pagoEm->toDateString(),
                'observacoes' => null,
            ]);

            if (! $repetir) {
                $financeiroDespesaFixa->update(['ativo' => false]);
            }

            $turno = CaixaTurno::query()
                ->where('empresa_id', $empresa->id)
                ->where('status', CaixaTurno::STATUS_ABERTO)
                ->first();

            if ($turno) {
                $descCaixa = 'Despesa fixa paga: '.$financeiroDespesaFixa->nome;
                if ($financeiroDespesaFixa->categoria) {
                    $descCaixa .= ' ('.$financeiroDespesaFixa->categoria.')';
                }
                if (strlen($descCaixa) > 500) {
                    $descCaixa = substr($descCaixa, 0, 497).'...';
                }

                CaixaMovimento::query()->create([
                    'caixa_turno_id' => $turno->id,
                    'user_id' => $request->user()->id,
                    'tipo' => CaixaMovimento::TIPO_SANGRIA,
                    'descricao' => $descCaixa,
                    'valor' => $data['valor'],
                ]);
                $lancouCaixa = true;
            }
        });

        $msg = 'Pagamento registrado em contas a pagar.';
        if ($lancouCaixa) {
            $msg .= ' Saída registrada no caixa (sangria no turno aberto).';
        } else {
            $msg .= ' Com o caixa fechado não há turno aberto — nada foi lançado no caixa; ao abrir, registre a saída em dinheiro se precisar.';
        }
        if (! $repetir) {
            $msg .= ' Esta despesa fixa foi desativada e não entra mais no total mensal.';
        }

        return redirect()->route('empresa.financeiro.despesas-fixas.index')->with('status', $msg);
    }

    public function despesasFixasEdit(Request $request, FinanceiroDespesaFixa $financeiroDespesaFixa): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $financeiroDespesaFixa->empresa_id !== (int) $empresa->id) {
            abort(404);
        }

        return view('empresa.financeiro.despesas-fixa-form', [
            'empresa' => $empresa,
            'despesa' => $financeiroDespesaFixa,
        ]);
    }

    public function despesasFixasUpdate(Request $request, FinanceiroDespesaFixa $financeiroDespesaFixa): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $financeiroDespesaFixa->empresa_id !== (int) $empresa->id) {
            abort(404);
        }

        $financeiroDespesaFixa->update($this->validatedDespesaFixa($request));

        return redirect()->route('empresa.financeiro.despesas-fixas.index')->with('status', 'Despesa fixa atualizada.');
    }

    public function despesasFixasDestroy(Request $request, FinanceiroDespesaFixa $financeiroDespesaFixa): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $financeiroDespesaFixa->empresa_id !== (int) $empresa->id) {
            abort(404);
        }

        $financeiroDespesaFixa->delete();

        return redirect()->route('empresa.financeiro.despesas-fixas.index')->with('status', 'Despesa fixa removida.');
    }

    public function receberIndex(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $titulos = $this->filtrarTitulos($empresa, FinanceiroTitulo::TIPO_RECEBER, $request);

        return view('empresa.financeiro.contas-receber', compact('empresa', 'titulos'));
    }

    public function receberCreate(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.financeiro.receber-form', ['empresa' => $empresa, 'titulo' => new FinanceiroTitulo]);
    }

    public function receberStore(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        FinanceiroTitulo::query()->create(
            $this->validatedTitulo($request) + [
                'empresa_id' => $empresa->id,
                'tipo' => FinanceiroTitulo::TIPO_RECEBER,
            ]
        );

        return redirect()->route('empresa.financeiro.contas-receber')->with('status', 'Título a receber cadastrado.');
    }

    public function receberEdit(Request $request, FinanceiroTitulo $financeiroTitulo): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_RECEBER)) {
            abort(404);
        }

        return view('empresa.financeiro.receber-form', ['empresa' => $empresa, 'titulo' => $financeiroTitulo]);
    }

    public function receberUpdate(Request $request, FinanceiroTitulo $financeiroTitulo): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_RECEBER)) {
            abort(404);
        }

        if ($financeiroTitulo->status === FinanceiroTitulo::STATUS_PAGO) {
            return redirect()
                ->route('empresa.financeiro.contas-receber')
                ->with('warning', 'Título já baixado não pode ser editado.');
        }

        $financeiroTitulo->update($this->validatedTitulo($request));

        return redirect()->route('empresa.financeiro.contas-receber')->with('status', 'Título atualizado.');
    }

    public function receberDestroy(Request $request, FinanceiroTitulo $financeiroTitulo): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_RECEBER)) {
            abort(404);
        }

        $financeiroTitulo->delete();

        return redirect()->route('empresa.financeiro.contas-receber')->with('status', 'Título removido.');
    }

    public function receberBaixar(Request $request, FinanceiroTitulo $financeiroTitulo): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_RECEBER)) {
            abort(404);
        }

        if ($financeiroTitulo->status === FinanceiroTitulo::STATUS_PAGO) {
            return back()->with('warning', 'Este título já está baixado.');
        }

        $financeiroTitulo->update([
            'status' => FinanceiroTitulo::STATUS_PAGO,
            'pago_em' => Carbon::today(),
        ]);

        return redirect()->route('empresa.financeiro.contas-receber')->with('status', 'Recebimento registrado.');
    }

    public function pagarIndex(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $titulos = $this->filtrarTitulos($empresa, FinanceiroTitulo::TIPO_PAGAR, $request);

        return view('empresa.financeiro.contas-pagar', compact('empresa', 'titulos'));
    }

    public function pagarCreate(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.financeiro.pagar-form', ['empresa' => $empresa, 'titulo' => new FinanceiroTitulo]);
    }

    public function pagarStore(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        FinanceiroTitulo::query()->create(
            $this->validatedTitulo($request) + [
                'empresa_id' => $empresa->id,
                'tipo' => FinanceiroTitulo::TIPO_PAGAR,
            ]
        );

        return redirect()->route('empresa.financeiro.contas-pagar')->with('status', 'Conta a pagar cadastrada.');
    }

    public function pagarEdit(Request $request, FinanceiroTitulo $financeiroTitulo): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_PAGAR)) {
            abort(404);
        }

        return view('empresa.financeiro.pagar-form', ['empresa' => $empresa, 'titulo' => $financeiroTitulo]);
    }

    public function pagarUpdate(Request $request, FinanceiroTitulo $financeiroTitulo): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_PAGAR)) {
            abort(404);
        }

        if ($financeiroTitulo->status === FinanceiroTitulo::STATUS_PAGO) {
            return redirect()
                ->route('empresa.financeiro.contas-pagar')
                ->with('warning', 'Título já pago não pode ser editado.');
        }

        $financeiroTitulo->update($this->validatedTitulo($request));

        return redirect()->route('empresa.financeiro.contas-pagar')->with('status', 'Título atualizado.');
    }

    public function pagarDestroy(Request $request, FinanceiroTitulo $financeiroTitulo): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_PAGAR)) {
            abort(404);
        }

        $financeiroTitulo->delete();

        return redirect()->route('empresa.financeiro.contas-pagar')->with('status', 'Título removido.');
    }

    public function pagarBaixar(Request $request, FinanceiroTitulo $financeiroTitulo): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || ! $this->assertTipo($financeiroTitulo, FinanceiroTitulo::TIPO_PAGAR)) {
            abort(404);
        }

        if ($financeiroTitulo->status === FinanceiroTitulo::STATUS_PAGO) {
            return back()->with('warning', 'Este título já está pago.');
        }

        $financeiroTitulo->update([
            'status' => FinanceiroTitulo::STATUS_PAGO,
            'pago_em' => Carbon::today(),
        ]);

        return redirect()->route('empresa.financeiro.contas-pagar')->with('status', 'Pagamento registrado.');
    }

    /**
     * @return Collection<int, FinanceiroTitulo>
     */
    private function filtrarTitulos(Empresa $empresa, string $tipo, Request $request)
    {
        $q = FinanceiroTitulo::query()
            ->where('empresa_id', $empresa->id)
            ->where('tipo', $tipo)
            ->orderBy('vencimento')
            ->orderBy('id');

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->value().'%';
            $q->where(function ($sub) use ($needle) {
                $sub->where('contraparte', 'like', $needle)
                    ->orWhere('descricao', 'like', $needle);
            });
        }

        if ($request->filled('situacao')) {
            $s = $request->string('situacao')->value();
            $hoje = Carbon::today()->toDateString();
            if ($s === 'aberto') {
                $q->where('status', FinanceiroTitulo::STATUS_ABERTO)
                    ->whereDate('vencimento', '>=', $hoje);
            } elseif ($s === 'atrasado') {
                $q->where('status', FinanceiroTitulo::STATUS_ABERTO)
                    ->whereDate('vencimento', '<', $hoje);
            } elseif ($s === 'pago') {
                $q->where('status', FinanceiroTitulo::STATUS_PAGO);
            }
        }

        return $q->limit(200)->get();
    }

    private function assertTipo(FinanceiroTitulo $titulo, string $tipo): bool
    {
        return $titulo->tipo === $tipo;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTitulo(Request $request): array
    {
        $data = $request->validate([
            'contraparte' => ['nullable', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:500'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'vencimento' => ['required', 'date'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ]);

        $data['status'] = FinanceiroTitulo::STATUS_ABERTO;
        $data['pago_em'] = null;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDespesaFixa(Request $request): array
    {
        $rules = [
            'nome' => ['required', 'string', 'max:255'],
            'valor_mensal' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'categoria' => ['nullable', 'string', 'max:120'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];
        if (Schema::hasColumn('financeiro_despesas_fixas', 'vencimento')) {
            $rules['vencimento'] = ['nullable', 'date'];
        }

        $data = $request->validate($rules);
        $data['ativo'] = $request->boolean('ativo');
        if (Schema::hasColumn('financeiro_despesas_fixas', 'vencimento')) {
            $data['vencimento'] = ! empty($data['vencimento']) ? $data['vencimento'] : null;
        }

        return $data;
    }
}
