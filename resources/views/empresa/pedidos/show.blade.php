@extends('layouts.empresa')

@section('title', 'Pedido '.$pedido->codigo_publico)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Pedidos', 'url' => route('empresa.pedidos.index')],
        ['label' => $pedido->codigo_publico, 'url' => route('empresa.pedidos.show', $pedido)],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">{{ $pedido->codigo_publico }}</h2>
                        <div class="small text-muted">Criado em {{ $pedido->created_at->format('d/m/Y H:i') }} · Canal {{ $pedido->canal === \App\Models\Pedido::CANAL_LOJA ? 'loja online' : $pedido->canal }}</div>
                    </div>
                    <span class="vf-badge {{ $pedido->classeBadgeStatus() }} align-self-start">{{ $pedido->rotuloStatus() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 vf-table">
                        <thead><tr><th>Item</th><th class="text-center">Qtd</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($pedido->itens as $it)
                                <tr>
                                    <td>
                                        {{ $it->nome_produto }}
                                        @include('partials.opcoes-pedido-item', ['opcoesLinha' => $it->opcoes_linha])
                                    </td>
                                    <td class="text-center">{{ $it->quantidade }}</td>
                                    <td class="text-end">R$ {{ number_format((float) $it->subtotal, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="2">Subtotal</th><th class="text-end">R$ {{ number_format((float) $pedido->subtotal, 2, ',', '.') }}</th></tr>
                            <tr><th colspan="2">Entrega</th><th class="text-end">R$ {{ number_format((float) $pedido->taxa_entrega, 2, ',', '.') }}</th></tr>
                            <tr><th colspan="2">Total</th><th class="text-end text-success">R$ {{ number_format((float) $pedido->total, 2, ',', '.') }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-2">Cliente</h3>
                <p class="mb-1 fw-medium">{{ $pedido->cliente_nome }}</p>
                <p class="small text-muted mb-1">{{ $pedido->cliente_telefone }}</p>
                @if ($pedido->cliente_email)
                    <p class="small text-muted mb-1">{{ $pedido->cliente_email }}</p>
                @endif
                <p class="small mb-0">{{ $pedido->endereco }}@if ($pedido->complemento)<br>{{ $pedido->complemento }}@endif</p>
                <p class="small mt-2 mb-0"><strong>Pagamento:</strong> {{ $pedido->rotuloFormaPagamento() }}</p>
                @if ($pedido->observacoes)
                    <p class="small mt-2 mb-0"><strong>Obs.:</strong> {{ $pedido->observacoes }}</p>
                @endif
            </div>
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-3">Status do pedido</h3>
                <form action="{{ route('empresa.pedidos.status', $pedido) }}" method="post">
                    @csrf
                    @method('PUT')
                    <select class="form-select form-select-sm mb-2" name="status" required>
                        @foreach (\App\Models\Pedido::statusRotulos() as $val => $rotulo)
                            <option value="{{ $val }}" @selected($pedido->status === $val)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Atualizar status</button>
                </form>
            </div>
            <a href="{{ route('empresa.pedidos.index') }}" class="btn btn-outline-secondary w-100">Voltar à lista</a>
        </div>
    </div>
@endsection
