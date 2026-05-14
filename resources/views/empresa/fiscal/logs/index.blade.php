@extends('layouts.empresa')

@section('title', 'Fiscal — Logs')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Logs fiscais', 'url' => route('empresa.fiscal.logs.index')],
    ]])

    <h2 class="h4 fw-bold mb-3">Logs fiscais</h2>

    <form method="get" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1" for="tipo">Tipo</label>
                <select class="form-select form-select-sm" id="tipo" name="tipo" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\Fiscal\FiscalLogTipo::cases() as $t)
                        <option value="{{ $t->value }}" @selected(($tipo ?? '') === $t->value)>{{ $t->rotulo() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Mensagem</th>
                        <th>Pedido</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $lg)
                        <tr>
                            <td class="small text-nowrap">{{ $lg->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td><code>{{ $lg->tipo->value }}</code></td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($lg->mensagem ?? '—', 200) }}</td>
                            <td class="small">
                                @if ($lg->nota?->pedido)
                                    <a href="{{ route('empresa.pedidos.show', $lg->nota->pedido) }}">{{ $lg->nota->pedido->codigo_publico }}</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Nenhum log.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
