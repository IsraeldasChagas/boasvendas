@extends('layouts.empresa')

@section('title', 'API — Tokens')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
        ['label' => 'API', 'url' => route('empresa.api.status')],
        ['label' => 'Tokens'],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if (session('vf_api_token_plain'))
        <div class="alert alert-warning border-warning mb-3">
            <div class="fw-semibold mb-2">Token gerado (copie agora)</div>
            <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control font-monospace" id="vf-api-token-plain" readonly value="{{ session('vf_api_token_plain') }}">
                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('vf-api-token-plain').value)">Copiar</button>
            </div>
            <div class="small text-muted mb-2">Teste com:</div>
            <pre class="small bg-dark text-light p-2 rounded mb-0"><code>curl -sS -H "Authorization: Bearer {{ session('vf_api_token_plain') }}" -H "Accept: application/json" "{{ $statusUrl }}"</code></pre>
        </div>
    @endif

    <h2 class="h5 fw-bold mb-3">Tokens da API</h2>
    @include('empresa.api._nav')

    @if ($podeGerenciar)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h3 class="h6 fw-semibold mb-3">Novo token</h3>
                <form action="{{ route('empresa.api.tokens.store') }}" method="post" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label small" for="nome">Nome da integração</label>
                        <input type="text" class="form-control form-control-sm @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', 'Integração homologação') }}" required maxlength="120" placeholder="Ex.: ERP homologação">
                        @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small" for="environment">Ambiente</label>
                        <select class="form-select form-select-sm" id="environment" name="environment" required>
                            @foreach ($environments as $key => $label)
                                <option value="{{ $key }}" @selected(old('environment', 'homologacao') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small">Permissões (abilities)</label>
                        <div class="row g-2">
                            @foreach ($abilitiesCatalog as $key => $label)
                                <div class="col-md-6 col-lg-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="abilities[]" id="ability-{{ Str::slug($key) }}" value="{{ $key }}"
                                            @checked(in_array($key, (array) old('abilities', ['api.visualizar']), true))>
                                        <label class="form-check-label small" for="ability-{{ Str::slug($key) }}">
                                            <code>{{ $key }}</code> — {{ $label }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-key me-1"></i>Gerar token</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-secondary">Somente gestores e operadores podem criar ou revogar tokens.</div>
    @endif

    <div class="table-responsive border rounded-3 bg-white">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nome</th>
                    <th>Prefixo</th>
                    <th>Ambiente</th>
                    <th>Abilities</th>
                    <th>Expira</th>
                    <th>Último uso</th>
                    <th>Status</th>
                    @if ($podeGerenciar)<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse ($tokens as $token)
                    <tr>
                        <td>{{ $token->nome }}</td>
                        <td><code>{{ $token->token_prefix ?: '—' }}…</code></td>
                        <td>{{ $token->rotuloEnvironment() }}</td>
                        <td class="small">{{ implode(', ', $token->abilities ?? []) ?: '—' }}</td>
                        <td>{{ $token->expires_at?->format('d/m/Y H:i') ?? 'Sem expiração' }}</td>
                        <td>{{ $token->last_used_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>
                            @if ($token->isRevoked())
                                <span class="badge text-bg-secondary">Revogado</span>
                            @elseif ($token->isExpired())
                                <span class="badge text-bg-warning">Expirado</span>
                            @else
                                <span class="badge text-bg-success">Ativo</span>
                            @endif
                        </td>
                        @if ($podeGerenciar)
                            <td class="text-end">
                                @if (! $token->isRevoked())
                                    <form action="{{ route('empresa.api.tokens.revoke', $token) }}" method="post" class="d-inline" onsubmit="return confirm('Revogar este token? Integrações que o usam deixarão de funcionar.');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Revogar</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $podeGerenciar ? 8 : 7 }}" class="text-center text-muted py-4">Nenhum token cadastrado. @if ($podeGerenciar) Gere um acima para testar a API. @endif</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
