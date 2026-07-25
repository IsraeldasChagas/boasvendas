@extends('layouts.empresa')

@section('title', 'Insumos')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Insumos', 'url' => route('empresa.insumos.index')],
    ]])

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">Insumos (ingredientes)</h2>
            <p class="small text-muted mb-0">O que você usa para produzir: polpa, leite, copo, embalagem. Medidos em kg/g, L/ml ou unidades.</p>
        </div>
        <a href="{{ route('empresa.insumos.create') }}" class="btn btn-primary btn-sm">Novo insumo</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if ($abaixoMinimo > 0)
        <div class="alert alert-warning small">
            <strong>{{ $abaixoMinimo }}</strong> insumo(s) no estoque mínimo ou abaixo. Reponha antes de faltar na cozinha.
        </div>
    @endif

    <form action="{{ route('empresa.insumos.index') }}" method="get" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" class="form-control form-control-sm" style="max-width: 20rem" name="q" value="{{ request('q') }}" placeholder="Buscar insumo…">
        <button type="submit" class="btn btn-sm btn-primary">Buscar</button>
        @if (request('q'))
            <a href="{{ route('empresa.insumos.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
        @endif
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table align-middle">
                <thead>
                    <tr>
                        <th style="width:3.5rem"></th>
                        <th>Insumo</th>
                        <th>Medida</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-end">Mínimo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($insumos as $i)
                        <tr>
                            <td>
                                @if ($i->urlFoto())
                                    <img src="{{ $i->urlFoto() }}" alt="" width="40" height="40" class="rounded object-fit-cover border">
                                @else
                                    <span class="d-inline-flex align-items-center justify-content-center bg-light border rounded text-muted" style="width:40px;height:40px">
                                        <i class="bi bi-basket"></i>
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-medium">{{ $i->nome }}</div>
                                @unless ($i->ativo)<span class="vf-badge bg-secondary-subtle text-secondary">Inativo</span>@endunless
                            </td>
                            <td class="small text-muted">{{ $i->unidadeBase()->rotulo() }}</td>
                            <td class="text-end fw-semibold {{ $i->abaixoDoMinimo() ? 'text-warning' : '' }}">{{ $i->saldoFormatado() }}</td>
                            <td class="text-end small text-muted">
                                {{ (float) $i->estoque_minimo > 0 ? \App\Enums\Estoque\UnidadeMedida::formatar((float) $i->estoque_minimo, $i->unidadeBase()) : '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('empresa.insumos.movimentos', $i) }}" class="btn btn-sm btn-outline-primary">Movimentar</a>
                                <a href="{{ route('empresa.insumos.edit', $i) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhum insumo cadastrado. Comece pelos itens que você compra (ex.: polpa de açaí, leite condensado, copo).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
