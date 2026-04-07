<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\VeAcerto;
use App\Models\VeFiado;
use App\Models\VePonto;
use App\Models\VeRemessa;
use App\Models\VeVendaExternaRegistro;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendaExternaController extends Controller
{
    public const MAX_DIAS_PERIODO_VE = 120;

    public function dashboard(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa para ver venda externa.');
        }

        Carbon::setLocale('pt_BR');

        $empresaId = $empresa->id;

        $pontosAtivos = VePonto::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VePonto::STATUS_ATIVO)
            ->count();

        $remessasEmCampo = VeRemessa::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VeRemessa::STATUS_EM_CAMPO)
            ->count();

        $fiadoAberto = (float) VeFiado::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VeFiado::STATUS_ABERTO)
            ->sum('valor');

        $limiteAcerto = Carbon::now()->addDays(7);
        $acertosPendentes = VePonto::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VePonto::STATUS_ATIVO)
            ->whereNotNull('proximo_acerto_em')
            ->where('proximo_acerto_em', '<=', $limiteAcerto)
            ->count();

        $proximosAcertos = VePonto::query()
            ->where('empresa_id', $empresaId)
            ->whereNotNull('proximo_acerto_em')
            ->orderBy('proximo_acerto_em')
            ->limit(8)
            ->get();

        $weekStart = Carbon::now()->subWeeks(11)->startOfWeek();
        $chartLabels = [];
        $chartValores = [];
        for ($i = 0; $i < 12; $i++) {
            $ws = $weekStart->copy()->addWeeks($i);
            $we = $ws->copy()->endOfWeek();
            $chartLabels[] = $ws->format('d/m');
            $chartValores[] = (float) VeVendaExternaRegistro::query()
                ->where('empresa_id', $empresaId)
                ->whereBetween('data_venda', [$ws->toDateString(), $we->toDateString()])
                ->sum('valor');
        }

        $chartMax = max(1.0, ...$chartValores);

        return view('empresa.venda-externa.dashboard', compact(
            'empresa',
            'pontosAtivos',
            'remessasEmCampo',
            'fiadoAberto',
            'acertosPendentes',
            'proximosAcertos',
            'chartLabels',
            'chartValores',
            'chartMax'
        ));
    }

    public function pontosIndex(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        Carbon::setLocale('pt_BR');

        $query = VePonto::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome');

        if ($request->filled('q')) {
            $needle = '%'.$request->string('q')->trim()->value().'%';
            $query->where(function ($sub) use ($needle) {
                $sub->where('nome', 'like', $needle)
                    ->orWhere('regiao', 'like', $needle);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        $pontos = $query->get();

        return view('empresa.venda-externa.pontos', compact('empresa', 'pontos'));
    }

    public function pontosCreate(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $ponto = new VePonto;

        return view('empresa.venda-externa.pontos-form', compact('empresa', 'ponto'));
    }

    public function pontosStore(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $this->mergeDatasAcertoVazias($request);

        $data = $this->validatedPonto($request);
        $data['empresa_id'] = $empresa->id;

        VePonto::query()->create($data);

        return redirect()->route('empresa.venda-externa.pontos')->with('status', 'Ponto cadastrado.');
    }

    public function pontosEdit(Request $request, VePonto $vePonto): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $vePonto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $ponto = $vePonto;

        return view('empresa.venda-externa.pontos-form', compact('empresa', 'ponto'));
    }

    public function pontosUpdate(Request $request, VePonto $vePonto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $vePonto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $this->mergeDatasAcertoVazias($request);

        $vePonto->update($this->validatedPonto($request));

        return redirect()->route('empresa.venda-externa.pontos')->with('status', 'Ponto atualizado.');
    }

    public function pontosDestroy(Request $request, VePonto $vePonto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $vePonto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $vePonto->delete();

        return redirect()->route('empresa.venda-externa.pontos')->with('status', 'Ponto removido.');
    }

    private function mergeDatasAcertoVazias(Request $request): void
    {
        $request->merge([
            'proximo_acerto_em' => $request->filled('proximo_acerto_em') ? $request->input('proximo_acerto_em') : null,
            'ultimo_acerto_em' => $request->filled('ultimo_acerto_em') ? $request->input('ultimo_acerto_em') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPonto(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'regiao' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([VePonto::STATUS_ATIVO, VePonto::STATUS_PAUSADO])],
            'proximo_acerto_em' => ['nullable', 'date'],
            'ultimo_acerto_em' => ['nullable', 'date'],
        ]);
    }

    public function remessasIndex(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $empresaId = $empresa->id;

        $query = VeRemessa::query()
            ->where('empresa_id', $empresaId)
            ->with(['ponto', 'produto'])
            ->withCount(['acertos as acertos_concluidos_count' => function ($q) {
                $q->where('status', VeAcerto::STATUS_CONCLUIDO);
            }])
            ->orderByDesc('created_at');

        if ($request->filled('produto_id')) {
            $query->where('produto_id', $request->integer('produto_id'));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->value();
            if ($status === 'acertado') {
                $query->whereHas('acertos', function ($q) {
                    $q->where('status', VeAcerto::STATUS_CONCLUIDO);
                });
            }
            if ($status === 'nao_acertado') {
                $query->whereDoesntHave('acertos', function ($q) {
                    $q->where('status', VeAcerto::STATUS_CONCLUIDO);
                });
            }
        }

        if ($request->filled('ve_ponto_id')) {
            $query->where('ve_ponto_id', $request->integer('ve_ponto_id'));
        }

        $remessas = $query->limit(200)->get();

        $pontosFiltro = VePonto::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $produtosFiltro = Produto::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->limit(500)
            ->get();

        return view('empresa.venda-externa.remessas.index', compact('empresa', 'remessas', 'pontosFiltro', 'produtosFiltro'));
    }

    public function remessasCreate(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $remessa = new VeRemessa;
        $pontos = VePonto::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome')
            ->get();

        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome')
            ->limit(500)
            ->get();

        return view('empresa.venda-externa.remessas.form', compact('empresa', 'remessa', 'pontos', 'produtos'));
    }

    public function remessasStore(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $this->mergeRemessaPontoVazio($request);

        [$data, $entregaAcerto] = $this->validatedEntregaForm($request, $empresa->id);
        $data['empresa_id'] = $empresa->id;

        DB::transaction(function () use ($data, $entregaAcerto, $empresa) {
            $remessa = VeRemessa::query()->create($data);
            $this->aplicarAcertoNaEntrega($remessa, $entregaAcerto, $empresa->id);
        });

        return redirect()->route('empresa.venda-externa.remessas.index')->with('status', 'Entrega cadastrada.');
    }

    public function remessasShow(Request $request, VeRemessa $veRemessa): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veRemessa->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $veRemessa->load(['ponto', 'produto']);
        $remessa = $veRemessa;

        return view('empresa.venda-externa.remessas.show', compact('empresa', 'remessa'));
    }

    public function remessasEdit(Request $request, VeRemessa $veRemessa): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veRemessa->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $remessa = $veRemessa;
        $pontos = VePonto::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome')
            ->get();

        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('nome')
            ->limit(500)
            ->get();

        return view('empresa.venda-externa.remessas.form', compact('empresa', 'remessa', 'pontos', 'produtos'));
    }

    public function remessasUpdate(Request $request, VeRemessa $veRemessa): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veRemessa->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $this->mergeRemessaPontoVazio($request);

        [$data, $entregaAcerto] = $this->validatedEntregaForm($request, $empresa->id);

        DB::transaction(function () use ($veRemessa, $data, $entregaAcerto, $empresa) {
            $veRemessa->update($data);
            $veRemessa->refresh();
            $this->aplicarAcertoNaEntrega($veRemessa, $entregaAcerto, $empresa->id);
        });

        return redirect()->route('empresa.venda-externa.remessas.show', $veRemessa)->with('status', 'Entrega atualizada.');
    }

    public function remessasDestroy(Request $request, VeRemessa $veRemessa): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veRemessa->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $veRemessa->delete();

        return redirect()->route('empresa.venda-externa.remessas.index')->with('status', 'Entrega excluída.');
    }

    private function mergeRemessaPontoVazio(Request $request): void
    {
        $request->merge([
            've_ponto_id' => $request->filled('ve_ponto_id') ? $request->input('ve_ponto_id') : null,
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function validatedEntregaForm(Request $request, int $empresaId): array
    {
        $rules = [
            'entrega_acerto' => ['required', Rule::in(['acertado', 'nao_acertado'])],
            've_ponto_id' => [
                'nullable',
                'integer',
                Rule::exists('ve_pontos', 'id')->where('empresa_id', $empresaId),
                Rule::requiredIf(fn () => $request->string('entrega_acerto')->value() === 'acertado'),
            ],
        ];

        if (Schema::hasColumn('ve_remessas', 'produto_id')) {
            $rules['produto_id'] = ['required', 'integer', Rule::exists('produtos', 'id')->where('empresa_id', $empresaId)];
        }

        $validated = $request->validate($rules);

        $entregaAcerto = $validated['entrega_acerto'];
        unset($validated['entrega_acerto']);

        $validated['status'] = VeRemessa::STATUS_EM_CAMPO;

        if (Schema::hasColumn('ve_remessas', 'produto_id') && ! empty($validated['produto_id'])) {
            $nome = Produto::query()
                ->where('empresa_id', $empresaId)
                ->whereKey($validated['produto_id'])
                ->value('nome');
            $validated['titulo'] = is_string($nome) ? $nome : null;
        } else {
            $validated['titulo'] = null;
        }

        return [$validated, $entregaAcerto];
    }

    private function aplicarAcertoNaEntrega(VeRemessa $remessa, string $entregaAcerto, int $empresaId): void
    {
        VeAcerto::query()
            ->where('empresa_id', $empresaId)
            ->where('ve_remessa_id', $remessa->id)
            ->where('status', VeAcerto::STATUS_CONCLUIDO)
            ->delete();

        if ($entregaAcerto !== 'acertado') {
            return;
        }

        $payload = [
            'empresa_id' => $empresaId,
            've_ponto_id' => $remessa->ve_ponto_id,
            've_remessa_id' => $remessa->id,
            'data_acerto' => now()->toDateString(),
            'valor_vendas' => null,
            'valor_repasse' => null,
            'status' => VeAcerto::STATUS_CONCLUIDO,
            'observacoes' => null,
        ];
        if (Schema::hasColumn('ve_acertos', 'valor_repasse_unitario')) {
            $payload['valor_repasse_unitario'] = null;
        }
        VeAcerto::query()->create($payload);
    }

    public function acertosIndex(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $empresaId = $empresa->id;

        $query = VeAcerto::query()
            ->where('empresa_id', $empresaId)
            ->with(['ponto', 'remessa'])
            ->orderByRaw('CASE WHEN data_acerto IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByDesc('data_acerto')
            ->orderByDesc('created_at');

        // `ac_status` evita conflito com outras convenções; `status` mantém URLs antigas.
        $rawStatus = $request->input('ac_status', $request->input('status'));
        $statusFiltro = is_scalar($rawStatus) ? trim((string) $rawStatus) : '';
        if ($statusFiltro === '' || ! in_array($statusFiltro, [VeAcerto::STATUS_ABERTO, VeAcerto::STATUS_CONCLUIDO], true)) {
            $statusFiltro = VeAcerto::STATUS_ABERTO;
        }

        if ($statusFiltro === VeAcerto::STATUS_ABERTO) {
            $query->where(function ($q): void {
                $q->where('status', VeAcerto::STATUS_ABERTO)
                    ->orWhereNull('status');
            });
        } else {
            $query->where('status', VeAcerto::STATUS_CONCLUIDO);
        }

        $pontoId = $request->input('ve_ponto_id');
        if ($pontoId !== null && $pontoId !== '' && (int) $pontoId > 0) {
            $query->where('ve_ponto_id', (int) $pontoId);
        }

        $acertos = $query->limit(200)->get();

        $pontosFiltro = VePonto::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        return view('empresa.venda-externa.acertos.index', compact('empresa', 'acertos', 'pontosFiltro', 'statusFiltro'));
    }

    public function acertosCreate(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $acerto = new VeAcerto;
        $pontos = VePonto::query()->where('empresa_id', $empresa->id)->orderBy('nome')->get();
        $remessas = VeRemessa::query()
            ->where('empresa_id', $empresa->id)
            ->with('produto')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('empresa.venda-externa.acertos.form', compact('empresa', 'acerto', 'pontos', 'remessas'));
    }

    public function acertosStore(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $this->mergeAcertoRemessaVazio($request);

        $data = $this->validatedAcerto($request, $empresa->id);
        $data['empresa_id'] = $empresa->id;
        $data = $this->aplicarDefaultsAcerto($data, $empresa->id);

        VeAcerto::query()->create($data);

        return redirect()->route('empresa.venda-externa.acertos')->with('status', 'Acerto registrado.');
    }

    public function acertosShow(Request $request, VeAcerto $veAcerto): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veAcerto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $veAcerto->load(['ponto', 'remessa.produto']);
        $acerto = $veAcerto;

        return view('empresa.venda-externa.acertos.show', compact('empresa', 'acerto'));
    }

    public function acertosEdit(Request $request, VeAcerto $veAcerto): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veAcerto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $acerto = $veAcerto;
        $pontos = VePonto::query()->where('empresa_id', $empresa->id)->orderBy('nome')->get();
        $remessas = VeRemessa::query()
            ->where('empresa_id', $empresa->id)
            ->with('produto')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('empresa.venda-externa.acertos.form', compact('empresa', 'acerto', 'pontos', 'remessas'));
    }

    public function acertosUpdate(Request $request, VeAcerto $veAcerto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veAcerto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $this->mergeAcertoRemessaVazio($request);

        $data = $this->validatedAcerto($request, $empresa->id);
        $data = $this->aplicarDefaultsAcerto($data, $empresa->id);

        $veAcerto->update($data);

        return redirect()->route('empresa.venda-externa.acertos.show', $veAcerto)->with('status', 'Acerto atualizado.');
    }

    public function acertosDestroy(Request $request, VeAcerto $veAcerto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veAcerto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $veAcerto->delete();

        return redirect()->route('empresa.venda-externa.acertos')->with('status', 'Acerto excluído.');
    }

    private function mergeAcertoRemessaVazio(Request $request): void
    {
        $request->merge([
            've_remessa_id' => $request->filled('ve_remessa_id') ? $request->input('ve_remessa_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAcerto(Request $request, int $empresaId): array
    {
        $rules = [
            've_ponto_id' => ['required', 'integer', Rule::exists('ve_pontos', 'id')->where('empresa_id', $empresaId)],
            've_remessa_id' => ['nullable', 'integer', Rule::exists('ve_remessas', 'id')->where('empresa_id', $empresaId)],
            'data_acerto' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => $request->input('status') === VeAcerto::STATUS_CONCLUIDO),
            ],
            'valor_repasse_unitario' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'quantidade' => ['nullable', 'numeric', 'min:0.001', 'max:99999999.999'],
            'valor_repasse' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'status' => ['required', Rule::in([VeAcerto::STATUS_ABERTO, VeAcerto::STATUS_CONCLUIDO])],
            'observacoes' => ['nullable', 'string', 'max:5000'],
        ];

        if (! Schema::hasColumn('ve_acertos', 'valor_repasse_unitario')) {
            unset($rules['valor_repasse_unitario']);
        }
        if (! Schema::hasColumn('ve_acertos', 'quantidade')) {
            unset($rules['quantidade']);
        }

        $data = $request->validate($rules);
        $data['valor_vendas'] = null;

        return $data;
    }

    private function aplicarDefaultsAcerto(array $data, int $empresaId): array
    {
        if (
            Schema::hasColumn('ve_acertos', 'valor_repasse_unitario')
            && empty($data['valor_repasse_unitario'])
            && ! empty($data['ve_remessa_id'])
        ) {
            $precoProduto = VeRemessa::query()
                ->where('empresa_id', $empresaId)
                ->whereKey($data['ve_remessa_id'])
                ->with('produto')
                ->first()?->produto?->preco;
            if ($precoProduto !== null) {
                $data['valor_repasse_unitario'] = (float) $precoProduto;
            }
        }

        if (
            Schema::hasColumn('ve_acertos', 'valor_repasse_unitario')
            && Schema::hasColumn('ve_acertos', 'quantidade')
            && ! empty($data['valor_repasse_unitario'])
            && ! empty($data['quantidade'])
        ) {
            $data['valor_repasse'] = round(((float) $data['valor_repasse_unitario']) * ((float) $data['quantidade']), 2);
        }

        return $data;
    }

    private function mergeFiadoPontoVazio(Request $request): void
    {
        $request->merge([
            've_ponto_id' => $request->filled('ve_ponto_id') ? $request->input('ve_ponto_id') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFiado(Request $request, int $empresaId): array
    {
        return $request->validate([
            'contraparte' => ['nullable', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:500'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            've_ponto_id' => ['nullable', 'integer', Rule::exists('ve_pontos', 'id')->where('empresa_id', $empresaId)],
            'vencimento' => ['nullable', 'date'],
            'status' => ['required', Rule::in([VeFiado::STATUS_ABERTO, VeFiado::STATUS_QUITADO])],
        ]);
    }

    public function fiadosIndex(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $empresaId = $empresa->id;

        $query = VeFiado::query()
            ->where('empresa_id', $empresaId)
            ->with('ponto')
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [VeFiado::STATUS_ABERTO])
            ->orderByRaw('CASE WHEN vencimento IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('vencimento')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('ve_ponto_id')) {
            $query->where('ve_ponto_id', $request->integer('ve_ponto_id'));
        }

        $fiados = $query->limit(200)->get();

        $pontosFiltro = VePonto::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        return view('empresa.venda-externa.fiados.index', compact('empresa', 'fiados', 'pontosFiltro'));
    }

    public function fiadosCreate(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $fiado = new VeFiado;
        $pontos = VePonto::query()->where('empresa_id', $empresa->id)->orderBy('nome')->get();

        return view('empresa.venda-externa.fiados.form', compact('empresa', 'fiado', 'pontos'));
    }

    public function fiadosStore(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $this->mergeFiadoPontoVazio($request);

        $data = $this->validatedFiado($request, $empresa->id);
        $data['empresa_id'] = $empresa->id;

        VeFiado::query()->create($data);

        return redirect()->route('empresa.venda-externa.fiados')->with('status', 'Fiado registrado.');
    }

    public function fiadosShow(Request $request, VeFiado $veFiado): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veFiado->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $veFiado->load('ponto');
        $fiado = $veFiado;

        return view('empresa.venda-externa.fiados.show', compact('empresa', 'fiado'));
    }

    public function fiadosEdit(Request $request, VeFiado $veFiado): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veFiado->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $fiado = $veFiado;
        $pontos = VePonto::query()->where('empresa_id', $empresa->id)->orderBy('nome')->get();

        return view('empresa.venda-externa.fiados.form', compact('empresa', 'fiado', 'pontos'));
    }

    public function fiadosUpdate(Request $request, VeFiado $veFiado): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veFiado->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $this->mergeFiadoPontoVazio($request);

        $veFiado->update($this->validatedFiado($request, $empresa->id));

        return redirect()->route('empresa.venda-externa.fiados.show', $veFiado)->with('status', 'Fiado atualizado.');
    }

    public function fiadosDestroy(Request $request, VeFiado $veFiado): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veFiado->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $veFiado->delete();

        return redirect()->route('empresa.venda-externa.fiados')->with('status', 'Fiado excluído.');
    }

    public function fiadosBaixar(Request $request, VeFiado $veFiado): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $veFiado->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        if (! $veFiado->podeDarBaixa()) {
            return redirect()
                ->route('empresa.venda-externa.fiados.show', $veFiado)
                ->with('warning', 'Este fiado já está quitado.');
        }

        $veFiado->update(['status' => VeFiado::STATUS_QUITADO]);

        return redirect()->route('empresa.venda-externa.fiados.show', $veFiado)->with('status', 'Fiado quitado.');
    }

    /**
     * @return array{empresa: Empresa, inicio: Carbon, fim: Carbon}|RedirectResponse
     */
    private function resolveVeRelatoriosPeriodo(Request $request): array|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $fim = Carbon::parse($request->input('fim', Carbon::today()->toDateString()))->endOfDay();
        $inicio = Carbon::parse($request->input('inicio', $fim->copy()->subDays(29)->toDateString()))->startOfDay();

        if ($fim->lt($inicio)) {
            [$inicio, $fim] = [$fim->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        if ($inicio->diffInDays($fim) > static::MAX_DIAS_PERIODO_VE) {
            $inicio = $fim->copy()->subDays(static::MAX_DIAS_PERIODO_VE)->startOfDay();
        }

        return compact('empresa', 'inicio', 'fim');
    }

    private function queryAcertosNoPeriodo(int $empresaId, Carbon $inicio, Carbon $fim)
    {
        return VeAcerto::query()
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($inicio, $fim) {
                $q->whereBetween('data_acerto', [$inicio->toDateString(), $fim->toDateString()])
                    ->orWhere(function ($q2) use ($inicio, $fim) {
                        $q2->whereNull('data_acerto')
                            ->whereBetween('created_at', [$inicio, $fim]);
                    });
            });
    }

    public function relatorios(Request $request): View|RedirectResponse
    {
        $resolved = $this->resolveVeRelatoriosPeriodo($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        ['empresa' => $empresa, 'inicio' => $inicio, 'fim' => $fim] = $resolved;

        Carbon::setLocale('pt_BR');

        $empresaId = $empresa->id;

        $totalVendasRegistradas = (float) VeVendaExternaRegistro::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('data_venda', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor');

        $totalVendasDeclaradasAcertos = (float) VeAcerto::query()
            ->where('empresa_id', $empresaId)
            ->whereNotNull('data_acerto')
            ->whereBetween('data_acerto', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor_repasse');

        $totalRepasseAcertos = (float) VeAcerto::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VeAcerto::STATUS_CONCLUIDO)
            ->whereNotNull('data_acerto')
            ->whereBetween('data_acerto', [$inicio->toDateString(), $fim->toDateString()])
            ->sum('valor_repasse');

        $fiadoAbertoSnapshot = (float) VeFiado::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VeFiado::STATUS_ABERTO)
            ->sum('valor');

        $remessasCriadasPeriodo = VeRemessa::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('created_at', [$inicio, $fim])
            ->count();

        $remessasEncerradasPeriodo = VeRemessa::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VeRemessa::STATUS_ENCERRADA)
            ->whereBetween('updated_at', [$inicio, $fim])
            ->count();

        $chartLabels = [];
        $chartSerieRegistros = [];
        $chartSerieAcertos = [];
        $weekEnd = $fim->copy()->endOfWeek();
        $weekStartAnchor = $weekEnd->copy()->subWeeks(11)->startOfWeek();
        for ($i = 0; $i < 12; $i++) {
            $ws = $weekStartAnchor->copy()->addWeeks($i);
            $we = $ws->copy()->endOfWeek();
            $chartLabels[] = $ws->format('d/m');
            $chartSerieRegistros[] = (float) VeVendaExternaRegistro::query()
                ->where('empresa_id', $empresaId)
                ->whereBetween('data_venda', [$ws->toDateString(), $we->toDateString()])
                ->sum('valor');
            $chartSerieAcertos[] = (float) VeAcerto::query()
                ->where('empresa_id', $empresaId)
                ->whereNotNull('data_acerto')
                ->whereBetween('data_acerto', [$ws->toDateString(), $we->toDateString()])
                ->sum('valor_repasse');
        }

        $chartMax = max(1.0, ...$chartSerieRegistros, ...$chartSerieAcertos);

        $acertosPeriodo = $this->queryAcertosNoPeriodo($empresaId, $inicio, $fim)
            ->with(['ponto', 'remessa.produto'])
            ->orderByDesc('data_acerto')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $fiadosAbertosLista = VeFiado::query()
            ->where('empresa_id', $empresaId)
            ->where('status', VeFiado::STATUS_ABERTO)
            ->with('ponto')
            ->orderByDesc('valor')
            ->limit(200)
            ->get();

        $inadimplenciaPorPonto = $fiadosAbertosLista
            ->groupBy('ve_ponto_id')
            ->map(function ($grupo) {
                $first = $grupo->first();

                return (object) [
                    'ponto' => $first->ponto,
                    'total' => (float) $grupo->sum('valor'),
                    'qtd' => $grupo->count(),
                    'atrasado_valor' => (float) $grupo->filter(fn (VeFiado $f) => $f->situacaoVisual() === 'atrasado')->sum('valor'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $remessasPeriodo = VeRemessa::query()
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($inicio, $fim) {
                $q->whereBetween('created_at', [$inicio, $fim])
                    ->orWhereBetween('updated_at', [$inicio, $fim]);
            })
            ->with('ponto')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('empresa.venda-externa.relatorios', compact(
            'empresa',
            'inicio',
            'fim',
            'totalVendasRegistradas',
            'totalVendasDeclaradasAcertos',
            'totalRepasseAcertos',
            'fiadoAbertoSnapshot',
            'remessasCriadasPeriodo',
            'remessasEncerradasPeriodo',
            'chartLabels',
            'chartSerieRegistros',
            'chartSerieAcertos',
            'chartMax',
            'acertosPeriodo',
            'inadimplenciaPorPonto',
            'remessasPeriodo',
        ));
    }

    public function relatoriosExportAcertos(Request $request): StreamedResponse|RedirectResponse
    {
        $resolved = $this->resolveVeRelatoriosPeriodo($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        ['empresa' => $empresa, 'inicio' => $inicio, 'fim' => $fim] = $resolved;

        $acertos = $this->queryAcertosNoPeriodo($empresa->id, $inicio, $fim)
            ->with(['ponto', 'remessa.produto'])
            ->orderByDesc('data_acerto')
            ->orderByDesc('id')
            ->limit(5000)
            ->get();

        $filename = sprintf(
            've_acertos_%s_%s.csv',
            $inicio->format('Ymd'),
            $fim->format('Ymd')
        );

        return response()->streamDownload(function () use ($acertos) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id',
                'data_acerto',
                'status',
                'ponto',
                'remessa_id',
                'valor_repasse_unitario',
                'valor_repasse_total',
                'observacoes',
            ], ';');

            foreach ($acertos as $a) {
                fputcsv($out, [
                    $a->id,
                    $a->data_acerto?->format('Y-m-d') ?? '',
                    $a->status,
                    $a->ponto?->nome ?? '',
                    $a->ve_remessa_id ?? '',
                    $a->valor_repasse_unitario !== null ? number_format((float) $a->valor_repasse_unitario, 2, '.', '') : '',
                    $a->valor_repasse !== null ? number_format((float) $a->valor_repasse, 2, '.', '') : '',
                    preg_replace('/\s+/', ' ', trim((string) $a->observacoes)),
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function relatoriosExportFiados(Request $request): StreamedResponse|RedirectResponse
    {
        $resolved = $this->resolveVeRelatoriosPeriodo($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        ['empresa' => $empresa] = $resolved;

        $fiados = VeFiado::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', VeFiado::STATUS_ABERTO)
            ->with('ponto')
            ->orderByDesc('valor')
            ->limit(5000)
            ->get();

        $filename = 've_fiados_abertos_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($fiados) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id',
                'ponto',
                'contraparte',
                'valor',
                'vencimento',
                'situacao',
                'descricao',
            ], ';');

            foreach ($fiados as $f) {
                fputcsv($out, [
                    $f->id,
                    $f->ponto?->nome ?? '',
                    $f->contraparte ?? '',
                    number_format((float) $f->valor, 2, '.', ''),
                    $f->vencimento?->format('Y-m-d') ?? '',
                    $f->rotuloSituacao(),
                    preg_replace('/\s+/', ' ', trim((string) $f->descricao)),
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function relatoriosExportRemessas(Request $request): StreamedResponse|RedirectResponse
    {
        $resolved = $this->resolveVeRelatoriosPeriodo($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        ['empresa' => $empresa, 'inicio' => $inicio, 'fim' => $fim] = $resolved;

        $remessas = VeRemessa::query()
            ->where('empresa_id', $empresa->id)
            ->where(function ($q) use ($inicio, $fim) {
                $q->whereBetween('created_at', [$inicio, $fim])
                    ->orWhereBetween('updated_at', [$inicio, $fim]);
            })
            ->with('ponto')
            ->orderByDesc('updated_at')
            ->limit(5000)
            ->get();

        $filename = sprintf(
            've_remessas_%s_%s.csv',
            $inicio->format('Ymd'),
            $fim->format('Ymd')
        );

        return response()->streamDownload(function () use ($remessas) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id',
                'titulo',
                'status',
                'ponto',
                'criada_em',
                'atualizada_em',
                'dias_criacao_ate_atualizacao',
            ], ';');

            foreach ($remessas as $r) {
                $dias = $r->created_at && $r->updated_at
                    ? $r->created_at->diffInDays($r->updated_at)
                    : '';
                fputcsv($out, [
                    $r->id,
                    $r->tituloExibicao(),
                    $r->status,
                    $r->ponto?->nome ?? '',
                    $r->created_at?->format('Y-m-d H:i:s') ?? '',
                    $r->updated_at?->format('Y-m-d H:i:s') ?? '',
                    $dias,
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
