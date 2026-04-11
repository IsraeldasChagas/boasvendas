@extends('layouts.empresa')

@section('title', 'Despesas fixas')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Financeiro', 'url' => route('empresa.financeiro.index')],
        ['label' => 'Despesas fixas', 'url' => route('empresa.financeiro.despesas-fixas.index')],
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
        <div>
            <h2 class="h5 fw-bold mb-0">Despesas fixas</h2>
            <p class="small text-muted mb-0">Aluguel, salários fixos, internet, gás, energia etc. — referência mensal para gestão.</p>
        </div>
        <a href="{{ route('empresa.financeiro.despesas-fixas.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nova despesa</a>
    </div>

    <div class="vf-card vf-card-stat mb-3">
        <div>
            <div class="small text-muted">Total mensal (ativas)</div>
            <div class="h4 mb-0 fw-bold text-danger">R$ {{ number_format($totalMensal, 2, ',', '.') }}</div>
        </div>
        <i class="bi bi-calendar-month fs-3 text-danger opacity-50"></i>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('financeiro_despesas_fixas', 'vencimento'))
                            <th class="text-nowrap">Vencimento</th>
                        @endif
                        <th class="text-end">Valor mensal (R$)</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($despesas as $d)
                        <tr class="{{ $d->ativo ? '' : 'text-muted' }}">
                            <td class="fw-semibold">{{ $d->nome }}</td>
                            <td class="small">{{ $d->categoria ?: '—' }}</td>
                            @if (\Illuminate\Support\Facades\Schema::hasColumn('financeiro_despesas_fixas', 'vencimento'))
                                <td class="small text-nowrap">{{ $d->vencimento ? $d->vencimento->format('d/m/Y') : '—' }}</td>
                            @endif
                            <td class="text-end">R$ {{ number_format((float) $d->valor_mensal, 2, ',', '.') }}</td>
                            <td><span class="vf-badge {{ $d->ativo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $d->ativo ? 'Ativa' : 'Inativa' }}</span></td>
                            <td class="text-end text-nowrap">
                                @if ($d->ativo)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-success js-pagar-despesa-fixa"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalPagarDespesaFixa"
                                        data-pagar-url="{{ route('empresa.financeiro.despesas-fixas.pagar', $d) }}"
                                        data-despesa-nome="{{ e($d->nome) }}"
                                        data-valor-mensal="{{ (float) $d->valor_mensal }}"
                                    >Pagar</button>
                                @endif
                                <a href="{{ route('empresa.financeiro.despesas-fixas.edit', $d) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form action="{{ route('empresa.financeiro.despesas-fixas.destroy', $d) }}" method="post" class="d-inline" onsubmit="return confirm('Excluir esta despesa fixa?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ \Illuminate\Support\Facades\Schema::hasColumn('financeiro_despesas_fixas', 'vencimento') ? 6 : 5 }}" class="text-center text-muted py-5">
                                Nenhuma despesa fixa cadastrada.
                                <a href="{{ route('empresa.financeiro.despesas-fixas.create') }}">Cadastrar</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modalPagarDespesaFixa" tabindex="-1" aria-labelledby="modalPagarDespesaFixaTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPagarDespesaFixaTitulo">Registrar pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formPagarDespesaFixa" method="post" action="{{ old('_pagar_url', '#') }}">
                    @csrf
                    <input type="hidden" name="_pagar_url" id="pagar-url-hidden" value="{{ old('_pagar_url') }}">
                    <div class="modal-body">
                        <p class="small text-muted mb-3" id="modalPagarDespesaFixaSubtitulo"></p>
                        <div class="mb-3">
                            <label class="form-label" for="pagar-valor">Valor pago (R$)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('valor') is-invalid @enderror" id="pagar-valor" name="valor" value="{{ old('valor') }}" required>
                            @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pagar-em">Data do pagamento</label>
                            <input type="date" class="form-control @error('pago_em') is-invalid @enderror" id="pagar-em" name="pago_em" value="{{ old('pago_em', now()->format('Y-m-d')) }}" required>
                            @error('pago_em')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <fieldset class="mb-0">
                            <legend class="form-label mb-2">Deseja manter esta conta nas despesas fixas para lançar de novo no próximo mês?</legend>
                            <div class="form-check">
                                <input class="form-check-input @error('repetir_proximo') is-invalid @enderror" type="radio" name="repetir_proximo" id="repetir-sim" value="1" @checked(old('repetir_proximo') === null || old('repetir_proximo') === '1' || old('repetir_proximo') === 1)>
                                <label class="form-check-label" for="repetir-sim">Sim — continua ativa no cadastro</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="repetir_proximo" id="repetir-nao" value="0" @checked(old('repetir_proximo') === '0' || old('repetir_proximo') === 0)>
                                <label class="form-check-label" for="repetir-nao">Não — desativa após este pagamento</label>
                            </div>
                            @error('repetir_proximo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </fieldset>
                        <p class="small text-muted mt-3 mb-0">O pagamento será registrado em <strong>Contas a pagar</strong> como já quitado. Se o <strong>caixa estiver aberto</strong>, a mesma saída entra no caixa como <strong>sangria</strong> (fluxo do dia e saldo do turno).</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar pagamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('modalPagarDespesaFixa');
    const form = document.getElementById('formPagarDespesaFixa');
    const subtitulo = document.getElementById('modalPagarDespesaFixaSubtitulo');
    const inputValor = document.getElementById('pagar-valor');
    const hiddenUrl = document.getElementById('pagar-url-hidden');
    if (!modal || !form || !subtitulo || !inputValor) return;

    function syncActionFromHidden() {
        if (hiddenUrl && hiddenUrl.value) {
            form.setAttribute('action', hiddenUrl.value);
        }
    }
    syncActionFromHidden();

    modal.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (!btn || !btn.classList.contains('js-pagar-despesa-fixa')) return;
        const url = btn.getAttribute('data-pagar-url');
        const nome = btn.getAttribute('data-despesa-nome') || '';
        const valor = btn.getAttribute('data-valor-mensal');
        if (url) {
            form.setAttribute('action', url);
            if (hiddenUrl) hiddenUrl.value = url;
        }
        subtitulo.textContent = nome ? 'Conta: ' + nome : '';
        if (valor) {
            inputValor.value = parseFloat(valor, 10).toFixed(2);
        }
    });

    @if ($errors->any() && old('_pagar_url'))
    if (typeof bootstrap !== 'undefined') {
        syncActionFromHidden();
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    @endif
})();
</script>
@endpush
