<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\CaixaMovimento;
use App\Models\CaixaTurno;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaixaController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa para usar o caixa.');
        }

        $turnoAberto = CaixaTurno::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', CaixaTurno::STATUS_ABERTO)
            ->with(['movimentos' => fn ($q) => $q->orderBy('created_at')])
            ->first();

        $historico = CaixaTurno::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', CaixaTurno::STATUS_FECHADO)
            ->orderByDesc('fechado_em')
            ->limit(10)
            ->get();

        return view('empresa.caixa.index', compact('empresa', 'turnoAberto', 'historico'));
    }

    public function abrir(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $request->validate([
            'valor_abertura' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'obs_abertura' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($empresa, $request, $data) {
                $jaAberto = CaixaTurno::query()
                    ->where('empresa_id', $empresa->id)
                    ->where('status', CaixaTurno::STATUS_ABERTO)
                    ->lockForUpdate()
                    ->exists();

                if ($jaAberto) {
                    throw new \RuntimeException('Já existe um caixa aberto.');
                }

                CaixaTurno::query()->create([
                    'empresa_id' => $empresa->id,
                    'user_id' => $request->user()->id,
                    'aberto_em' => Carbon::now(),
                    'valor_abertura' => $data['valor_abertura'],
                    'status' => CaixaTurno::STATUS_ABERTO,
                    'obs_abertura' => $data['obs_abertura'] ?? null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('empresa.caixa.index')->with('warning', $e->getMessage());
        }

        return redirect()->route('empresa.caixa.index')->with('status', 'Caixa aberto.');
    }

    public function movimento(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $turno = CaixaTurno::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', CaixaTurno::STATUS_ABERTO)
            ->first();

        if (! $turno) {
            return redirect()->route('empresa.caixa.index')->with('warning', 'Não há caixa aberto.');
        }

        $data = $request->validate([
            'tipo' => ['required', Rule::in([
                CaixaMovimento::TIPO_SUPRIMENTO,
                CaixaMovimento::TIPO_SANGRIA,
                CaixaMovimento::TIPO_VENDA_AVULSA,
            ])],
            'descricao' => ['required', 'string', 'max:500'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ]);

        CaixaMovimento::query()->create([
            'caixa_turno_id' => $turno->id,
            'user_id' => $request->user()->id,
            'tipo' => $data['tipo'],
            'descricao' => $data['descricao'],
            'valor' => $data['valor'],
        ]);

        return redirect()->route('empresa.caixa.index')->with('status', 'Movimento registrado.');
    }

    public function fechar(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $request->validate([
            'valor_conferido_fechamento' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'obs_fechamento' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($empresa, $data) {
                $turno = CaixaTurno::query()
                    ->where('empresa_id', $empresa->id)
                    ->where('status', CaixaTurno::STATUS_ABERTO)
                    ->lockForUpdate()
                    ->first();

                if (! $turno) {
                    throw new \RuntimeException('Não há caixa aberto.');
                }

                $turno->update([
                    'fechado_em' => Carbon::now(),
                    'valor_conferido_fechamento' => $data['valor_conferido_fechamento'],
                    'obs_fechamento' => $data['obs_fechamento'] ?? null,
                    'status' => CaixaTurno::STATUS_FECHADO,
                ]);
            });
        } catch (\RuntimeException $e) {
            return redirect()->route('empresa.caixa.index')->with('warning', $e->getMessage());
        }

        return redirect()->route('empresa.caixa.index')->with('status', 'Caixa fechado com sucesso.');
    }

    public function conferencia(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $turno = CaixaTurno::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', CaixaTurno::STATUS_ABERTO)
            ->with(['movimentos' => fn ($q) => $q->orderBy('created_at')])
            ->first();

        if (! $turno) {
            return redirect()->route('empresa.caixa.index')->with('warning', 'Abra o caixa para gerar a conferência.');
        }

        return view('empresa.caixa.conferencia', compact('empresa', 'turno'));
    }
}
