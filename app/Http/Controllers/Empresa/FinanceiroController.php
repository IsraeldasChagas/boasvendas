<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\FinanceiroTitulo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('empresa.financeiro.index', compact(
            'empresa',
            'aReceber',
            'aPagar',
            'saldoDia',
            'chartEntrada',
            'chartSaida',
            'chartLabels',
            'chartMax'
        ));
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
     * @return \Illuminate\Support\Collection<int, FinanceiroTitulo>
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
}
