@extends('layouts.empresa')

@section('title', 'API — Tokens')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
        ['label' => 'API', 'url' => route('empresa.api.status')],
        ['label' => 'Tokens'],
    ]])

    <h2 class="h5 fw-bold mb-3">Tokens da API</h2>
    @include('empresa.api._nav')

    <div class="alert alert-secondary">
        Criação e revogação completas serão liberadas em seguida. Tokens nunca são armazenados em texto puro — apenas o hash.
    </div>

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
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Nenhum token cadastrado ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
