@extends('layouts.empresa')

@section('title', 'Entregas')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Venda externa', 'url' => route('empresa.venda-externa.dashboard')],
        ['label' => 'Entregas', 'url' => route('empresa.venda-externa.remessas.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Entregas (para parceiro revender)</h2>
        <a href="{{ route('empresa.venda-externa.remessas.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova entrega</a>
    </div>

    <form action="{{ route('empresa.venda-externa.remessas.index') }}" method="get" class="vf-filter-bar mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3 col-lg-3">
                <label class="form-label small text-muted mb-1" for="rm-prod">Produto</label>
                <select class="form-select form-select-sm" id="rm-prod" name="produto_id">
                    <option value="">Todos</option>
                    @foreach ($produtosFiltro as $p)
                        <option value="{{ $p->id }}" @selected((string) request('produto_id') === (string) $p->id)>{{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label small text-muted mb-1" for="rm-st">Status</label>
                <select class="form-select form-select-sm" id="rm-st" name="status">
                    <option value="">Todos</option>
                    <option value="nao_acertado" @selected(request('status') === 'nao_acertado')>Não acertado</option>
                    <option value="acertado" @selected(request('status') === 'acertado')>Acertado</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-3">
                <label class="form-label small text-muted mb-1" for="rm-pt">Parceiro</label>
                <select class="form-select form-select-sm" id="rm-pt" name="ve_ponto_id">
                    <option value="">Todos</option>
                    @foreach ($pontosFiltro as $pt)
                        <option value="{{ $pt->id }}" @selected((string) request('ve_ponto_id') === (string) $pt->id)>{{ $pt->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto ms-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                <a href="{{ route('empresa.venda-externa.remessas.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </div>
    </form>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('ve_remessas', 'quantidade'))
                            <th class="text-end">Qtd.</th>
                        @endif
                        <th>Parceiro</th>
                        <th>Criada em</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($remessas as $r)
                        <tr>
                            <td class="fw-semibold">#{{ $r->id }}</td>
                            <td class="small">{{ $r->produto?->nome ?? $r->tituloExibicao() }}</td>
                            @if (\Illuminate\Support\Facades\Schema::hasColumn('ve_remessas', 'quantidade'))
                                <td class="small text-end fw-semibold">{{ (int) ($r->quantidade ?? 1) }}</td>
                            @endif
                            <td class="small">{{ $r->ponto?->nome ?? '—' }}</td>
                            <td class="small">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                            @php $acertada = (int) ($r->acertos_concluidos_count ?? 0) > 0; @endphp
                            <td><span class="vf-badge {{ $acertada ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">{{ $acertada ? 'Acertado' : 'Não acertado' }}</span></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('empresa.venda-externa.remessas.show', $r) }}" class="btn btn-sm btn-outline-primary">Abrir</a>
                                <a href="{{ route('empresa.venda-externa.remessas.edit', $r) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ \Illuminate\Support\Facades\Schema::hasColumn('ve_remessas', 'quantidade') ? 7 : 6 }}" class="text-center text-muted py-5">
                                Nenhuma entrega.
                                <a href="{{ route('empresa.venda-externa.remessas.create') }}">Cadastrar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
