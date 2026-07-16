<?php

namespace App\Http\Controllers\Empresa\Api;

use App\Http\Controllers\Controller;
use App\Models\EmpresaApiLog;
use App\Models\EmpresaApiToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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
            'statusUrl' => url('/api/v1/integration/status'),
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
            'environments' => EmpresaApiToken::environmentRotulos(),
            'podeGerenciar' => $this->podeGerenciarTokens($request->user()),
            'statusUrl' => url('/api/v1/integration/status'),
        ]);
    }

    public function storeToken(Request $request): RedirectResponse
    {
        $empresa = $this->empresaOrRedirect($request);
        if ($empresa instanceof RedirectResponse) {
            return $empresa;
        }

        if (! $this->podeGerenciarTokens($request->user())) {
            abort(403, 'Seu perfil não pode criar tokens da API.');
        }

        if (! Schema::hasTable('empresa_api_tokens')) {
            return redirect()
                ->route('empresa.api.tokens')
                ->with('warning', 'Tabelas da API ainda não existem. Rode: php artisan migrate');
        }

        $abilityKeys = array_keys(config('api.abilities', []));
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'environment' => ['required', 'string', Rule::in(array_keys(EmpresaApiToken::environmentRotulos()))],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', Rule::in($abilityKeys)],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $abilities = $data['abilities'] ?? [];
        if ($abilities === []) {
            $abilities = ['api.visualizar'];
        }

        $issued = EmpresaApiToken::issue(
            $empresa,
            $data['nome'],
            $abilities,
            $data['environment'],
            isset($data['expires_at']) ? new \DateTimeImmutable($data['expires_at']) : null,
            null,
            $request->user(),
        );

        return redirect()
            ->route('empresa.api.tokens')
            ->with('status', 'Token criado. Copie agora — ele não será exibido novamente.')
            ->with('vf_api_token_plain', $issued['plain']);
    }

    public function revokeToken(Request $request, EmpresaApiToken $empresaApiToken): RedirectResponse
    {
        $empresa = $this->empresaOrRedirect($request);
        if ($empresa instanceof RedirectResponse) {
            return $empresa;
        }

        if (! $this->podeGerenciarTokens($request->user())) {
            abort(403, 'Seu perfil não pode revogar tokens da API.');
        }

        if ((int) $empresaApiToken->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $empresaApiToken->revoke();

        return redirect()
            ->route('empresa.api.tokens')
            ->with('status', 'Token revogado.');
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

    private function podeGerenciarTokens(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->podeGerenciarUsuariosEquipe();
    }
}
