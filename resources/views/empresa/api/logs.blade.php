@extends('layouts.empresa')

@section('title', 'API — Logs')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
        ['label' => 'API', 'url' => route('empresa.api.status')],
        ['label' => 'Logs'],
    ]])

    <h2 class="h5 fw-bold mb-3">Logs da API</h2>
    @include('empresa.api._nav')

    <div class="table-responsive border rounded-3 bg-white">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Método</th>
                    <th>Endpoint</th>
                    <th>Token</th>
                    <th>IP</th>
                    <th>HTTP</th>
                    <th>ms</th>
                    <th>Erro</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="small">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td><code>{{ $log->method }}</code></td>
                        <td class="small"><code>{{ $log->endpoint }}</code></td>
                        <td class="small">{{ $log->token?->nome ?? '—' }}</td>
                        <td class="small">{{ $log->ip ?? '—' }}</td>
                        <td>{{ $log->status_http ?? '—' }}</td>
                        <td>{{ $log->duration_ms ?? '—' }}</td>
                        <td class="small text-danger">{{ $log->error ? \Illuminate\Support\Str::limit($log->error, 80) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Nenhum log registrado ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
