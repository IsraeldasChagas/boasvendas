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
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('vf_whatsapp_indisponivel'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('vf_whatsapp_indisponivel') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('vf_whatsapp_aviso_cliente'))
        <div class="alert alert-success border border-success-subtle d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-between gap-3" role="alert">
            <div class="small mb-0">
                <strong>Avisar o cliente no WhatsApp</strong> — ícone de <strong>loja</strong>, cliente, <strong>sacola</strong> com a palavra <strong>Código</strong> antes do número, status e link para acompanhar. Clique para abrir o app e enviar.
            </div>
            <a href="{{ session('vf_whatsapp_aviso_cliente') }}" target="_blank" rel="noopener noreferrer" class="btn btn-success text-nowrap flex-shrink-0">
                <i class="bi bi-whatsapp me-1"></i>Abrir WhatsApp do cliente
            </a>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">{{ $pedido->codigo_publico }}</h2>
                        <div class="small text-muted">Criado em {{ $pedido->created_at->format('d/m/Y H:i') }} · Canal {{ $pedido->canal === \App\Models\Pedido::CANAL_LOJA ? 'loja online' : $pedido->canal }} · {{ $pedido->rotuloTipoEntrega() }}@if ($pedido->cep_entrega) · CEP {{ substr($pedido->cep_entrega, 0, 5) }}-{{ substr($pedido->cep_entrega, 5) }}@endif</div>
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
                            <tr><th colspan="2">{{ ($pedido->tipo_entrega ?? \App\Models\Pedido::TIPO_ENTREGA_ENTREGA) === \App\Models\Pedido::TIPO_ENTREGA_BALCAO ? 'Retirada' : 'Taxa entrega' }}</th><th class="text-end">R$ {{ number_format((float) $pedido->taxa_entrega, 2, ',', '.') }}</th></tr>
                            <tr><th colspan="2">Total</th><th class="text-end text-success">R$ {{ number_format((float) $pedido->total, 2, ',', '.') }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @php
                $cupomImpressaoOk = ! \Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada')
                    || ($empresa->loja_impressao_pedido_habilitada ?? true);
            @endphp
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-2">Cupom do cliente</h3>
                <p class="small text-muted mb-3">Cupom estilo comanda (80&nbsp;mm): loja, pedido, itens com extras, valores e link para acompanhar. Use na <strong>impressora térmica</strong> pelo navegador ou envie o <strong>mesmo texto</strong> pelo WhatsApp.</p>
                <div class="d-grid gap-2">
                    @if ($cupomImpressaoOk)
                        <a href="{{ route('empresa.pedidos.imprimir', $pedido) }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer me-1"></i>Abrir cupom / imprimir</a>
                        <a href="{{ route('empresa.pedidos.imprimir', $pedido) }}?auto=1" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm"><i class="bi bi-lightning-charge me-1"></i>Abrir e pedir impressão</a>
                    @else
                        <p class="small text-muted mb-0">Impressão desativada em Configurações. Ainda pode enviar pelo WhatsApp.</p>
                    @endif
                    @if (! empty($cupomWhatsUrl ?? null))
                        <a href="{{ $cupomWhatsUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm"><i class="bi bi-whatsapp me-1"></i>Enviar cupom no WhatsApp</a>
                    @else
                        <p class="small text-warning mb-0">WhatsApp: confira o telefone do cliente (DDD + número).</p>
                    @endif
                </div>
            </div>
            @if (($pedido->tipo_entrega ?? \App\Models\Pedido::TIPO_ENTREGA_ENTREGA) === \App\Models\Pedido::TIPO_ENTREGA_ENTREGA && $pedido->entregador_token)
                @php
                    $urlEntregador = route('publico.entregador.show', [
                        'slug' => $empresa->slug,
                        'codigo' => $pedido->codigo_publico,
                        'token' => $pedido->entregador_token,
                    ], absolute: true);
                @endphp
                <div class="vf-card p-3 mb-3">
                    <h3 class="h6 fw-bold mb-2">Link do entregador</h3>
                    <p class="small text-muted mb-2">Mostra endereço, itens, pagamento e o <strong>código do pedido</strong> para conferir com o cliente. O entregador pode marcar <strong>entregue</strong>, <strong>cancelado</strong> ou <strong>endereço não encontrado</strong>.</p>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" readonly class="form-control font-monospace small user-select-all" id="vf-url-entregador" value="{{ $urlEntregador }}" aria-label="URL para o entregador">
                        <button type="button" class="btn btn-outline-primary" id="vf-copy-entregador">Copiar</button>
                    </div>
                    <a href="{{ $urlEntregador }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm w-100">Abrir página do entregador</a>
                </div>
                @push('scripts')
                    <script>
                        (function () {
                            var btn = document.getElementById('vf-copy-entregador');
                            var inp = document.getElementById('vf-url-entregador');
                            if (!btn || !inp) return;
                            btn.addEventListener('click', function () {
                                inp.select();
                                inp.setSelectionRange(0, 99999);
                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    navigator.clipboard.writeText(inp.value).catch(function () {});
                                }
                            });
                        })();
                    </script>
                @endpush
            @endif
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-2">Cliente</h3>
                <p class="mb-1 fw-medium">{{ $pedido->cliente_nome }}</p>
                <p class="small text-muted mb-1">{{ $pedido->cliente_telefone }}</p>
                @if ($pedido->cliente_email)
                    <p class="small text-muted mb-1">{{ $pedido->cliente_email }}</p>
                @endif
                <p class="small mb-0">{{ $pedido->endereco }}@if ($pedido->complemento)<br>{{ $pedido->complemento }}@endif</p>
                <p class="small mt-2 mb-0"><strong>Pagamento:</strong> {{ $pedido->descricaoPagamentoExibicao() }}</p>
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
