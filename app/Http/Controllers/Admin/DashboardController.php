<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $empresasAtivas = Empresa::query()->where('status', 'ativa')->count();
        $mrr = (float) Assinatura::query()->where('status', 'paga')->sum('valor_mensal');
        $empresasTrial = Empresa::query()->where('status', 'trial')->count();
        $assinaturasPendentes = Assinatura::query()->where('status', 'pendente')->count();

        $cards = [
            [
                'label' => 'Empresas ativas',
                'value' => (string) $empresasAtivas,
                'icon' => 'buildings',
                'tone' => 'success',
            ],
            [
                'label' => 'MRR (assinaturas pagas)',
                'value' => 'R$ '.number_format($mrr, 2, ',', '.'),
                'icon' => 'currency-dollar',
                'tone' => 'success',
            ],
            [
                'label' => 'Empresas em trial',
                'value' => (string) $empresasTrial,
                'icon' => 'person-badge',
                'tone' => 'primary',
            ],
            [
                'label' => 'Assinaturas pendentes',
                'value' => (string) $assinaturasPendentes,
                'icon' => 'credit-card',
                'tone' => $assinaturasPendentes > 0 ? 'warning' : 'success',
            ],
        ];

        $chartHeights = $this->empresasNovasPorMesAltura();

        $pendingJobs = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        $failedJobs = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;

        $healthJobs = $failedJobs > 0
            ? ['label' => 'Com falhas', 'tone' => 'danger']
            : ($pendingJobs > 500
                ? ['label' => 'Alta fila', 'tone' => 'warning']
                : ['label' => 'Normal', 'tone' => 'success']);

        $healthPagamentos = $assinaturasPendentes > 0
            ? ['label' => 'Monitorar', 'tone' => 'warning']
            : ['label' => 'OK', 'tone' => 'success'];

        return view('admin.dashboard', compact(
            'cards',
            'chartHeights',
            'pendingJobs',
            'failedJobs',
            'healthJobs',
            'healthPagamentos'
        ));
    }

    /**
     * @return list<int>
     */
    private function empresasNovasPorMesAltura(): array
    {
        $counts = [];
        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $counts[] = Empresa::query()
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        $max = max($counts) ?: 1;

        return array_map(fn (int $c): int => (int) round(($c / $max) * 100), $counts);
    }
}
