@extends('layouts.empresa')

@section('title', 'Insumo — '.$insumo->nome)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Insumos', 'url' => route('empresa.insumos.index')],
        ['label' => $insumo->nome, 'url' => '#'],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            @if ($insumo->urlFoto())
                <img src="{{ $insumo->urlFoto() }}" alt="" width="56" height="56" class="rounded border object-fit-cover">
            @endif
            <div>
                <h2 class="h4 fw-bold mb-1">{{ $insumo->nome }}</h2>
                <p class="small text-muted mb-0">
                    Saldo: <strong class="{{ $insumo->abaixoDoMinimo() ? 'text-warning' : '' }}">{{ $insumo->saldoFormatado() }}</strong>
                    · medido em {{ $insumo->unidadeBase()->rotulo() }}
                    · <a href="{{ route('empresa.insumos.edit', $insumo) }}">Editar cadastro</a>
                </p>
            </div>
        </div>
        <a href="{{ route('empresa.insumos.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @php $compativeis = $insumo->unidadeBase()->compativeis(); @endphp

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="vf-card p-3 h-100">
                <h3 class="h6 fw-bold mb-2"><i class="bi bi-plus-circle text-success me-1"></i>Entrada (compra)</h3>
                <form method="post" action="{{ route('empresa.insumos.repor', $insumo) }}" class="row g-2">
                    @csrf
                    <div class="col-5">
                        <input type="number" step="0.001" min="0.001" class="form-control form-control-sm" name="quantidade" placeholder="Qtd." required>
                    </div>
                    <div class="col-3">
                        <select class="form-select form-select-sm" name="unidade" required>
                            @foreach ($compativeis as $u)
                                <option value="{{ $u->value }}">{{ $u->sigla() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-success btn-sm w-100">Registrar</button>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control form-control-sm" name="observacao" maxlength="300" placeholder="Observação (ex.: compra no atacado)">
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="vf-card p-3 h-100">
                <h3 class="h6 fw-bold mb-2"><i class="bi bi-sliders text-primary me-1"></i>Ajustar saldo (contagem)</h3>
                <form method="post" action="{{ route('empresa.insumos.ajustar', $insumo) }}" class="row g-2">
                    @csrf
                    <div class="col-5">
                        <input type="number" step="0.001" min="0" class="form-control form-control-sm" name="novo_saldo" placeholder="Saldo real" required>
                    </div>
                    <div class="col-3">
                        <select class="form-select form-select-sm" name="unidade" required>
                            @foreach ($compativeis as $u)
                                <option value="{{ $u->value }}">{{ $u->sigla() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Ajustar</button>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control form-control-sm" name="observacao" maxlength="300" placeholder="Motivo (ex.: perda, contagem)">
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($usadoEm->isNotEmpty())
        <div class="vf-card p-3 mb-3">
            <h3 class="h6 fw-bold mb-2"><i class="bi bi-diagram-3 text-primary me-1"></i>Usado nas fichas técnicas</h3>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($usadoEm as $uso)
                    @if ($uso->produto)
                        <a href="{{ route('empresa.estoque.ficha', $uso->produto) }}" class="btn btn-sm btn-outline-secondary">
                            {{ $uso->produto->nome }} <span class="text-muted">· {{ $uso->quantidadeFormatada() }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <div class="vf-card p-0 overflow-hidden">
        <div class="p-3 pb-0">
            <h3 class="h6 fw-bold mb-0"><i class="bi bi-clock-history text-primary me-1"></i>Histórico (últimos 100)</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 vf-table align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th class="text-end">Movimento</th>
                        <th class="text-end">Saldo após</th>
                        <th>Observação</th>
                        <th>Por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimentos as $m)
                        <tr>
                            <td class="small text-nowrap">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="small">{{ $m->tipo?->rotulo() ?? '—' }}</td>
                            <td class="small text-end fw-semibold {{ $m->delta < 0 ? 'text-danger' : 'text-success' }}">{{ $m->deltaFormatado() }}</td>
                            <td class="small text-end">{{ $m->saldoAposFormatado() }}</td>
                            <td class="small text-muted">{{ $m->observacao ?: '—' }}</td>
                            <td class="small">{{ $m->user?->name ?? 'Sistema' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum movimento ainda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
