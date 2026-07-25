<?php

namespace App\Http\Controllers\Empresa\Fiscal;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\FiscalConfiguracao;
use App\Models\FiscalEmitente;
use App\Models\FiscalLog;
use App\Models\FiscalNota;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FiscalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Empresa $empresa */
        $empresa = $request->user()->empresa;
        abort_unless($empresa, 403);

        $hoje = Carbon::today();

        $emitidasHoje = 0;
        $rejeicoesHoje = 0;
        $faturamentoHoje = 0.0;
        $pendentes = 0;
        $serie7dias = [];

        if (Schema::hasTable('fiscal_notas')) {
            $emitidasHoje = FiscalNota::query()
                ->where('empresa_id', $empresa->id)
                ->where('status', 'autorizada')
                ->whereDate('data_emissao', $hoje)
                ->count();

            $rejeicoesHoje = FiscalNota::query()
                ->where('empresa_id', $empresa->id)
                ->where('status', 'rejeitada')
                ->whereDate('updated_at', $hoje)
                ->count();

            $faturamentoHoje = (float) FiscalNota::query()
                ->where('empresa_id', $empresa->id)
                ->where('status', 'autorizada')
                ->whereDate('data_emissao', $hoje)
                ->sum('valor_total');

            $pendentes = FiscalNota::query()
                ->where('empresa_id', $empresa->id)
                ->whereIn('status', ['aguardando_emissao', 'processando'])
                ->count();

            for ($i = 6; $i >= 0; $i--) {
                $d = $hoje->copy()->subDays($i);
                $serie7dias[] = [
                    'label' => $d->format('d/m'),
                    'autorizadas' => FiscalNota::query()
                        ->where('empresa_id', $empresa->id)
                        ->where('status', 'autorizada')
                        ->whereDate('data_emissao', $d)
                        ->count(),
                ];
            }
        }

        $emitentes = Schema::hasTable('fiscal_empresas')
            ? FiscalEmitente::query()->where('empresa_id', $empresa->id)->get()
            : collect();
        $emitentesAtivos = $emitentes->where('ativo', true)->count();
        $temEmitenteCompleto = $emitentes->contains(
            fn (FiscalEmitente $emitente) => $emitente->ativo && $emitente->cadastroFiscalCompleto()
        );

        $ultimosLogs = Schema::hasTable('fiscal_logs')
            ? FiscalLog::query()->where('empresa_id', $empresa->id)->orderByDesc('id')->limit(8)->get()
            : collect();

        $config = Schema::hasTable('fiscal_configuracoes')
            ? FiscalConfiguracao::obterOuCriarPadrao($empresa->id)
            : null;

        return view('empresa.fiscal.dashboard', compact(
            'empresa',
            'emitidasHoje',
            'rejeicoesHoje',
            'faturamentoHoje',
            'pendentes',
            'serie7dias',
            'emitentesAtivos',
            'temEmitenteCompleto',
            'ultimosLogs',
            'config',
        ));
    }
}
