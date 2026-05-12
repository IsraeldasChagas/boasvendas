@extends('layouts.empresa')

@section('title', 'Cartão Fidelidade')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fidelidade', 'url' => route('empresa.fidelidade.programa')],
        ['label' => 'Cartão Fidelidade', 'url' => route('empresa.fidelidade.cartoes')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Cartão Fidelidade</h2>
        <a href="{{ route('empresa.fidelidade.programa') }}" class="btn btn-outline-secondary btn-sm">Configurar programa</a>
    </div>

    @if (! $programa || ! $programa->ativo)
        <div class="alert alert-warning">Ative o programa em <a href="{{ route('empresa.fidelidade.programa') }}">Fidelidade</a> para registrar selos.</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="vf-card p-4">
                <h3 class="h6 fw-bold mb-3">Registrar compra (1 selo)</h3>
                <form action="{{ route('empresa.fidelidade.cartoes.selo') }}" method="post">
                    @csrf
                    <label class="form-label small" for="telefone-selo">Telefone do cliente</label>
                    <input type="tel" name="telefone" id="telefone-selo" class="form-control @error('telefone') is-invalid @enderror"
                           value="{{ old('telefone') }}" placeholder="(11) 98888-7777" {{ ! $programa || ! $programa->ativo ? 'disabled' : '' }} required>
                    @error('telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <button type="submit" class="btn btn-primary w-100 mt-3" @disabled(! $programa || ! $programa->ativo)>Adicionar selo</button>
                </form>
                <p class="small text-muted mt-3 mb-0">Na vitrine, o cliente pode ativar o cartão no checkout; aqui você ainda pode lançar selos manualmente.</p>
            </div>
        </div>
        <div class="col-lg-7">
            <form action="{{ route('empresa.fidelidade.cartoes') }}" method="get" class="vf-filter-bar mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1" for="q-cart">Buscar (cliente, telefone ou código)</label>
                        <input type="search" class="form-control form-control-sm" id="q-cart" name="q" value="{{ $q }}" placeholder="Nome, DDD, VF-…">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small text-muted mb-1" for="status-cart">Status do cartão</label>
                        <select class="form-select form-select-sm" id="status-cart" name="status">
                            <option value="" @selected(($statusCartao ?? '') === '')>Todos</option>
                            <option value="{{ \App\Models\FidelidadeCartao::STATUS_ATIVO }}" @selected(($statusCartao ?? '') === \App\Models\FidelidadeCartao::STATUS_ATIVO)>Ativo</option>
                            <option value="{{ \App\Models\FidelidadeCartao::STATUS_INATIVO }}" @selected(($statusCartao ?? '') === \App\Models\FidelidadeCartao::STATUS_INATIVO)>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Filtrar</button>
                        <a href="{{ route('empresa.fidelidade.cartoes') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                    </div>
                </div>
            </form>

            <div class="vf-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 vf-table align-middle">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Telefone</th>
                                <th>Código</th>
                                <th>Selos</th>
                                <th>Pontos</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cartoes as $c)
                                @php
                                    $waUrl = $c->cliente
                                        ? \App\Support\FidelidadeCartaoWhatsappLink::urlMensagemCartao($c->cliente, $c)
                                        : \App\Support\FidelidadeCartaoWhatsappLink::urlMensagemCartaoPorTelefone($c);
                                @endphp
                                <tr>
                                    <td class="small">
                                        @if ($c->cliente)
                                            <span class="fw-medium">{{ $c->cliente->nome }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small font-monospace">{{ $c->telefoneMascarado() }}</td>
                                    <td class="small font-monospace">{{ $c->codigo_fidelidade ?: '—' }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $c->selos }}</span>
                                        @if ($programa)
                                            <span class="text-muted small">/ {{ $programa->pedidos_meta }}</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $c->pontos ?? 0 }}</td>
                                    <td>
                                        @if (($c->status ?? \App\Models\FidelidadeCartao::STATUS_ATIVO) === \App\Models\FidelidadeCartao::STATUS_ATIVO)
                                            <span class="vf-badge bg-success-subtle text-success">Ativo</span>
                                        @else
                                            <span class="vf-badge bg-secondary-subtle text-secondary">Inativo</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap gap-1 justify-content-end">
                                            @if ($waUrl)
                                                <a href="{{ $waUrl }}" class="btn btn-sm btn-success" target="_blank" rel="noopener">WhatsApp</a>
                                            @else
                                                <span class="btn btn-sm btn-outline-secondary disabled">WhatsApp</span>
                                            @endif
                                            <a href="{{ route('empresa.fidelidade.cartoes.historico', $c) }}" class="btn btn-sm btn-outline-secondary">Histórico</a>
                                            <form action="{{ route('empresa.fidelidade.cartoes.pontos', $c) }}" method="post" class="d-flex gap-1 align-items-center">
                                                @csrf
                                                <input type="number" name="pontos" class="form-control form-control-sm" style="width:4.5rem" min="0" max="999999" value="{{ (int) ($c->pontos ?? 0) }}" title="Pontos">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Pontos</button>
                                            </form>
                                            <form action="{{ route('empresa.fidelidade.cartoes.toggle-status', $c) }}" method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    {{ ($c->status ?? \App\Models\FidelidadeCartao::STATUS_ATIVO) === \App\Models\FidelidadeCartao::STATUS_ATIVO ? 'Inativar' : 'Ativar' }}
                                                </button>
                                            </form>
                                            @if ($programa && $programa->ativo && $c->podeResgatar($programa))
                                                <form action="{{ route('empresa.fidelidade.cartoes.resgatar', $c) }}" method="post" class="d-inline" onsubmit="return confirm('Confirmar que a recompensa foi entregue ao cliente? Os selos da meta serão debitados.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Resgate</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Nenhum cartão encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
