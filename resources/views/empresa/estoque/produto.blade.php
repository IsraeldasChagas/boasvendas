@extends('layouts.empresa')

@section('title', 'Estoque — '.$produto->nome)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Estoque', 'url' => route('empresa.estoque.index')],
        ['label' => $produto->nome, 'url' => '#'],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">{{ $produto->nome }}</h2>
            <p class="small text-muted mb-0">
                Saldo atual:
                <strong class="{{ $produto->controlaEstoque() && $produto->estoque <= $limiarBaixo ? 'text-warning' : '' }}">
                    {{ $produto->controlaEstoque() ? $produto->estoque : 'sem controle' }}
                </strong>
                · <a href="{{ route('empresa.produtos.edit', $produto) }}">Editar produto</a>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('empresa.estoque.ficha', $produto) }}" class="btn btn-outline-primary btn-sm">Ficha técnica</a>
            <a href="{{ route('empresa.estoque.index') }}" class="btn btn-outline-secondary btn-sm">Voltar ao estoque</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="vf-card p-3 h-100">
                <h3 class="h6 fw-bold mb-2"><i class="bi bi-plus-circle text-success me-1"></i>Repor estoque</h3>
                <p class="small text-muted">Chegou mercadoria? Some ao saldo atual.</p>
                <form method="post" action="{{ route('empresa.estoque.repor', $produto) }}" class="row g-2">
                    @csrf
                    <div class="col-4">
                        <input type="number" class="form-control form-control-sm" name="quantidade" min="1" max="1000000" placeholder="Qtd." required>
                    </div>
                    <div class="col-8">
                        <input type="text" class="form-control form-control-sm" name="observacao" maxlength="300" placeholder="Observação (ex.: compra da feira)">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm">Repor</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="vf-card p-3 h-100">
                <h3 class="h6 fw-bold mb-2"><i class="bi bi-sliders text-primary me-1"></i>Ajustar saldo (inventário)</h3>
                <p class="small text-muted">Contou e o número está diferente? Defina o saldo certo.</p>
                <form method="post" action="{{ route('empresa.estoque.ajustar', $produto) }}" class="row g-2">
                    @csrf
                    <div class="col-4">
                        <input type="number" class="form-control form-control-sm" name="novo_saldo" min="0" max="1000000" placeholder="Saldo" value="{{ $produto->estoque }}" required>
                    </div>
                    <div class="col-8">
                        <input type="text" class="form-control form-control-sm" name="observacao" maxlength="300" placeholder="Motivo (ex.: contagem mensal)">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">Ajustar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php $porcoes = $produto->porcoesPossiveisPelaFicha(); @endphp
    <div class="vf-card p-3 mb-3">
        <h3 class="h6 fw-bold mb-2"><i class="bi bi-diagram-3 text-primary me-1"></i>Ficha técnica (receita)</h3>
        @if ($porcoes !== null)
            <p class="small mb-2">
                Com os insumos atuais, ainda dá para fazer <strong>{{ $porcoes }}</strong> porção(ões).
            </p>
        @else
            <p class="small text-muted mb-2">
                Sem receita cadastrada. Monte a ficha com os ingredientes, quantidades e modo de preparo — a venda passa a baixar os insumos.
            </p>
        @endif
        <a href="{{ route('empresa.estoque.ficha', $produto) }}" class="btn btn-outline-primary btn-sm">Abrir ficha técnica</a>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="p-3 pb-0">
            <h3 class="h6 fw-bold mb-0"><i class="bi bi-clock-history text-primary me-1"></i>Histórico de movimentos (últimos 100)</h3>
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
                            <td class="small text-end fw-semibold {{ $m->delta < 0 ? 'text-danger' : 'text-success' }}">
                                {{ $m->delta > 0 ? '+' : '' }}{{ $m->delta }}
                            </td>
                            <td class="small text-end">{{ $m->saldo_apos }}</td>
                            <td class="small text-muted">{{ $m->observacao ?: '—' }}</td>
                            <td class="small">{{ $m->user?->name ?? 'Sistema' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum movimento ainda. Vendas e reposições aparecerão aqui.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
