<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Assinatura;
use App\Models\SuporteTicket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $empresa = auth()->user()->empresa?->load('plano');

        $assinatura = null;
        $ticketsAbertos = 0;
        $ticketsRecentes = collect();
        $chartHeights = [];

        if ($empresa) {
            $assinatura = Assinatura::query()
                ->where('empresa_id', $empresa->id)
                ->orderByDesc('id')
                ->first();

            if (! $assinatura) {
                $assinatura = Assinatura::query()
                    ->where('empresa_nome', $empresa->nome)
                    ->orderByDesc('id')
                    ->first();
            }

            $ticketsAbertos = SuporteTicket::query()
                ->where('empresa_id', $empresa->id)
                ->whereIn('status', ['aberto', 'aguardando', 'em_andamento'])
                ->count();

            $ticketsRecentes = SuporteTicket::query()
                ->where('empresa_id', $empresa->id)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get();

            $counts = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = now()->subDays($i)->toDateString();
                $counts[] = SuporteTicket::query()
                    ->where('empresa_id', $empresa->id)
                    ->whereDate('created_at', $day)
                    ->count();
            }
            $max = max($counts) ?: 1;
            $chartHeights = array_map(fn (int $c): int => (int) round(($c / $max) * 100), $counts);
        }

        return view('empresa.dashboard', compact(
            'empresa',
            'assinatura',
            'ticketsAbertos',
            'ticketsRecentes',
            'chartHeights'
        ));
    }
}
