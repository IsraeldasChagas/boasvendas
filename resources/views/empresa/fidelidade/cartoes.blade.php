@extends('layouts.empresa')

@section('title', 'Fidelidade — cartões')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fidelidade', 'url' => route('empresa.fidelidade.programa')],
        ['label' => 'Cartões', 'url' => route('empresa.fidelidade.cartoes')],
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
        <h2 class="h5 fw-bold mb-0">Cartões e selos</h2>
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
                <p class="small text-muted mt-3 mb-0">Quando os pedidos da vitrine forem integrados, isso poderá ocorrer automaticamente ao concluir o pedido.</p>
            </div>
        </div>
        <div class="col-lg-7">
            <form action="{{ route('empresa.fidelidade.cartoes') }}" method="get" class="vf-filter-bar mb-3">
                <label class="form-label small text-muted mb-1" for="q-cart">Buscar por telefone</label>
                <div class="input-group input-group-sm">
                    <input type="search" class="form-control" id="q-cart" name="q" value="{{ $q }}" placeholder="DDD + número">
                    <button class="btn btn-outline-secondary" type="submit">Buscar</button>
                </div>
            </form>

            <div class="vf-card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 vf-table">
                        <thead>
                            <tr>
                                <th>Telefone</th>
                                <th>Selos</th>
                                <th>Resgates</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cartoes as $c)
                                <tr>
                                    <td class="small font-monospace">{{ $c->telefoneMascarado() }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $c->selos }}</span>
                                        @if ($programa)
                                            <span class="text-muted small">/ {{ $programa->pedidos_meta }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $c->total_resgates }}</td>
                                    <td class="text-end">
                                        @if ($programa && $programa->ativo && $c->podeResgatar($programa))
                                            <form action="{{ route('empresa.fidelidade.cartoes.resgatar', $c) }}" method="post" class="d-inline" onsubmit="return confirm('Confirmar que a recompensa foi entregue ao cliente? Os selos da meta serão debitados.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Registrar resgate</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Nenhum cartão encontrado. Adicione o primeiro selo ao lado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
