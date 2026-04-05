@extends('layouts.admin')

@section('title', 'Suporte')

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <form action="{{ route('admin.suporte.index') }}" method="get" class="vf-filter-bar mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small text-muted mb-1" for="filtro-q">Buscar</label>
                        <input type="search" class="form-control form-control-sm" id="filtro-q" name="q" value="{{ request('q') }}" placeholder="Assunto, empresa…">
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="filtro-status">Status</label>
                        <select class="form-select form-select-sm" id="filtro-status" name="status">
                            <option value="">Todos</option>
                            @foreach (\App\Models\SuporteTicket::statusRotulos() as $valor => $rotulo)
                                <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small text-muted mb-1" for="filtro-prioridade">Prioridade</label>
                        <select class="form-select form-select-sm" id="filtro-prioridade" name="prioridade">
                            <option value="">Todas</option>
                            @foreach (\App\Models\SuporteTicket::prioridades() as $valor => $rotulo)
                                <option value="{{ $valor }}" @selected(request('prioridade') === $valor)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-auto ms-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Filtrar</button>
                        <a href="{{ route('admin.suporte.index') }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    </div>
                </div>
            </form>

            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('admin.suporte.create') }}" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Novo ticket
                </a>
            </div>

            <div class="vf-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 vf-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Empresa</th>
                                <th>Assunto</th>
                                <th>Atualizado</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tickets as $t)
                                @php
                                    $pTone = match ($t->prioridade) {
                                        'alta' => 'danger',
                                        'media' => 'warning',
                                        default => 'secondary',
                                    };
                                    $sTone = match ($t->status) {
                                        'aberto' => 'info',
                                        'aguardando' => 'secondary',
                                        'em_andamento' => 'primary',
                                        'resolvido' => 'success',
                                        default => 'dark',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold"><a href="{{ route('admin.suporte.show', $t) }}" class="text-decoration-none">#{{ $t->id }}</a></td>
                                    <td>{{ $t->empresa?->nome ?? '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($t->assunto, 60) }}</td>
                                    <td class="small">{{ $t->updated_at->format('d/m/Y H:i') }}</td>
                                    <td><span class="vf-badge bg-{{ $pTone }}-subtle text-{{ $pTone }}">{{ \App\Models\SuporteTicket::prioridades()[$t->prioridade] }}</span></td>
                                    <td><span class="vf-badge bg-{{ $sTone }}-subtle text-{{ $sTone }}">{{ \App\Models\SuporteTicket::statusRotulos()[$t->status] }}</span></td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.suporte.show', $t) }}" class="btn btn-sm btn-outline-secondary">Ver</a>
                                        <a href="{{ route('admin.suporte.edit', $t) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        Nenhum ticket. <a href="{{ route('admin.suporte.create') }}">Abrir ticket</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3">
                <h2 class="h6 fw-bold mb-3">Resumo (dados reais)</h2>
                <p class="small text-muted mb-2">Tickets em aberto / andamento: <strong>{{ $abertos }}</strong></p>
                <p class="small text-muted mb-2">Resolvidos (últimos 7 dias): <strong>{{ $resolvidos7d }}</strong></p>
                <p class="small text-muted mb-0">SLA de 1ª resposta: <strong>—</strong> <span class="text-muted">(campo a definir)</span></p>
            </div>
        </div>
    </div>
@endsection
