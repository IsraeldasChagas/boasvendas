<?php

namespace App\Http\Controllers\Empresa\Api;

use App\Http\Controllers\Controller;
use App\Models\EmpresaApiLog;
use App\Models\EmpresaApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Painel básico da API (Fase 1) — listagens e status, sem CRUD completo.
 */
class ApiPainelController extends Controller
{
    public function status(Request $request): View|RedirectResponse
    {
        $empresa = $this->empresaOrRedirect($request);
        if ($empresa instanceof RedirectResponse) {
            return $empresa;
        }

        $tokensAtivos = 0;
        $tokensTotal = 0;
        $logsHoje = 0;
        $ultimoUso = null;

        if (Schema::hasTable('empresa_api_tokens')) {
            $tokensTotal = EmpresaApiToken::query()->where('empresa_id', $empresa->id)->count();
            $tokensAtivos = EmpresaApiToken::query()->where('empresa_id', $empresa->id)->ativos()->count();
            $ultimoUso = EmpresaApiToken::query()
                ->where('empresa_id', $empresa->id)
                ->whereNotNull('last_used_at')
                ->orderByDesc('last_used_at')
                ->value('last_used_at');
        }

        if (Schema::hasTable('empresa_api_logs')) {
            $logsHoje = EmpresaApiLog::query()
                ->where('empresa_id', $empresa->id)
                ->whereDate('created_at', today())
                ->count();
        }

        return view('empresa.api.status', [
            'empresa' => $empresa,
            'tokensAtivos' => $tokensAtivos,
            'tokensTotal' => $tokensTotal,
            'logsHoje' => $logsHoje,
            'ultimoUso' => $ultimoUso,
            'apiVersion' => config('api.version', '1.0'),
            'integrationTypes' => config('api.integration_types', []),
        ]);
    }

    public function tokens(Request $request): View|RedirectResponse
    {
        $empresa = $this->empresaOrRedirect($request);
        if ($empresa instanceof RedirectResponse) {
            return $empresa;
        }

        $tokens = collect();
        if (Schema::hasTable('empresa_api_tokens')) {
            $tokens = EmpresaApiToken::query()
                ->where('empresa_id', $empresa->id)
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        return view('empresa.api.tokens', [
            'empresa' => $empresa,
            'tokens' => $tokens,
            'abilitiesCatalog' => config('api.abilities', []),
        ]);
    }

    public function aplicacoes(Request $request): View|RedirectResponse
    {
        $empresa = $this->empresaOrRedirect($request);
        if ($empresa instanceof RedirectResponse) {
            return $empresa;
        }

        return view('empresa.api.aplicacoes', [
            'empresa' => $empresa,
            'integrationTypes' => config('api.integration_types', []),
        ]);
    }

    public function logs(Request $request): View|RedirectResponse
    {
        $empresa = $this->empresaOrRedirect($request);
        if ($empresa instanceof RedirectResponse) {
            return $empresa;
        }

        $logs = collect();
        if (Schema::hasTable('empresa_api_logs')) {
            $logs = EmpresaApiLog::query()
                ->where('empresa_id', $empresa->id)
                ->with('token:id,nome,token_prefix')
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        return view('empresa.api.logs', [
            'empresa' => $empresa,
            'logs' => $logs,
        ]);
    }

    public function ambiente(Request $request): View|RedirectResponse
    {
        $empresa = $this->empresaOrRedirect($request);
        if ($empresa instanceof RedirectResponse) {
            return $empresa;
        }

        return view('empresa.api.ambiente', [
            'empresa' => $empresa,
            'environments' => EmpresaApiToken::environmentRotulos(),
            'appEnv' => config('app.env'),
            'apiVersion' => config('api.version', '1.0'),
            'rateLimit' => (int) config('api.rate_limit_per_minute', 60),
        ]);
    }

    private function empresaOrRedirect(Request $request): mixed
    {
        $empresa = $request->user()?->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para acessar a API.');
        }

        return $empresa;
    }
}
