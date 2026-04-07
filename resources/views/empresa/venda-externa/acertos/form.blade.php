@extends('layouts.empresa')

@section('title', $acerto->exists ? 'Editar acerto' : 'Novo acerto')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Acertos', 'url' => route('empresa.venda-externa.acertos')],
        ['label' => $acerto->exists ? 'Editar #'.$acerto->id : 'Novo', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 38rem;">
        <h2 class="h5 fw-bold mb-4">{{ $acerto->exists ? 'Editar acerto #'.$acerto->id : 'Novo acerto' }}</h2>
        <form action="{{ $acerto->exists ? route('empresa.venda-externa.acertos.update', $acerto) : route('empresa.venda-externa.acertos.store') }}" method="post">
            @csrf
            @if ($acerto->exists)
                @method('PUT')
            @endif
            <div class="mb-3">
                <label class="form-label" for="ve_ponto_id">Parceiro</label>
                <select class="form-select @error('ve_ponto_id') is-invalid @enderror" id="ve_ponto_id" name="ve_ponto_id" required>
                    <option value="">Selecione…</option>
                    @foreach ($pontos as $pt)
                        <option value="{{ $pt->id }}" @selected((string) old('ve_ponto_id', $acerto->ve_ponto_id) === (string) $pt->id)>{{ $pt->nome }}</option>
                    @endforeach
                </select>
                @error('ve_ponto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="ve_remessa_id">Entrega / remessa (opcional)</label>
                <select class="form-select @error('ve_remessa_id') is-invalid @enderror" id="ve_remessa_id" name="ve_remessa_id">
                    <option value="">— Nenhuma —</option>
                    @foreach ($remessas as $rm)
                        <option
                            value="{{ $rm->id }}"
                            data-produto-nome="{{ $rm->produto?->nome ?? '' }}"
                            data-produto-preco="{{ $rm->produto && $rm->produto->preco !== null ? number_format((float) $rm->produto->preco, 2, '.', '') : '' }}"
                            @selected((string) old('ve_remessa_id', $acerto->ve_remessa_id) === (string) $rm->id)>
                            R-{{ $rm->id }} — {{ $rm->produto?->nome ?? $rm->tituloExibicao() }}
                        </option>
                    @endforeach
                </select>
                @error('ve_remessa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text border rounded px-2 py-2 bg-light mt-2 d-none" id="vf-acerto-produto-ref">
                    <span class="text-muted">Valor unitário do produto (cadastro):</span>
                    <strong id="vf-acerto-produto-ref-preco">—</strong>
                    <span class="text-muted" id="vf-acerto-produto-ref-nome-wrap"> · <span id="vf-acerto-produto-ref-nome"></span></span>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (\App\Models\VeAcerto::rotulosStatus() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(old('status', $acerto->status ?: \App\Models\VeAcerto::STATUS_ABERTO) === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <p class="small text-muted mb-0 mt-1">Em <strong>Concluído</strong> a data do acerto é obrigatória.</p>
            </div>
            <div class="mb-3">
                <label class="form-label" for="data_acerto">Data do acerto</label>
                <input type="date" class="form-control @error('data_acerto') is-invalid @enderror" id="data_acerto" name="data_acerto" value="{{ old('data_acerto', $acerto->data_acerto?->format('Y-m-d')) }}">
                @error('data_acerto')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="valor_repasse_unitario">Repasse unitário (R$)</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('valor_repasse_unitario') is-invalid @enderror" id="valor_repasse_unitario" name="valor_repasse_unitario" value="{{ old('valor_repasse_unitario', $acerto->valor_repasse_unitario) }}">
                    @error('valor_repasse_unitario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Valor acertado com o parceiro por unidade (pode coincidir com o preço do produto ou não).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="quantidade">Quantidade vendida</label>
                    <input type="number" step="0.001" min="0" class="form-control @error('quantidade') is-invalid @enderror" id="quantidade" name="quantidade" value="{{ old('quantidade', $acerto->quantidade) }}">
                    @error('quantidade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Quantidade que o parceiro vendeu (é isso que calcula o repasse total).</div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="valor_repasse">Repasse total (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control @error('valor_repasse') is-invalid @enderror" id="valor_repasse" name="valor_repasse" value="{{ old('valor_repasse', $acerto->valor_repasse) }}" readonly>
                @error('valor_repasse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Calculado automaticamente: repasse unitário × quantidade.</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="observacoes">Observações</label>
                <textarea class="form-control @error('observacoes') is-invalid @enderror" id="observacoes" name="observacoes" rows="2">{{ old('observacoes', $acerto->observacoes) }}</textarea>
                @error('observacoes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                @if ($acerto->exists)
                    <a href="{{ route('empresa.venda-externa.acertos.show', $acerto) }}" class="btn btn-outline-secondary">Cancelar</a>
                @else
                    <a href="{{ route('empresa.venda-externa.acertos') }}" class="btn btn-outline-secondary">Cancelar</a>
                @endif
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const sel = document.getElementById('ve_remessa_id');
            const box = document.getElementById('vf-acerto-produto-ref');
            const elPreco = document.getElementById('vf-acerto-produto-ref-preco');
            const elNome = document.getElementById('vf-acerto-produto-ref-nome');
            const wrapNome = document.getElementById('vf-acerto-produto-ref-nome-wrap');
            function fmtBr(val) {
                const n = parseFloat(val);
                if (Number.isNaN(n)) return '—';
                return n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function sync() {
                if (!sel || !box) return;
                const opt = sel.options[sel.selectedIndex];
                if (!sel.value) {
                    box.classList.add('d-none');
                    return;
                }
                box.classList.remove('d-none');
                const precoRaw = opt.getAttribute('data-produto-preco');
                const nome = opt.getAttribute('data-produto-nome') || '';
                if (precoRaw !== null && precoRaw !== '') {
                    elPreco.textContent = 'R$ ' + fmtBr(precoRaw);
                    const inputRepasseUnit = document.getElementById('valor_repasse_unitario');
                    if (inputRepasseUnit && inputRepasseUnit.value === '') {
                        inputRepasseUnit.value = parseFloat(precoRaw).toFixed(2);
                    }
                } else {
                    elPreco.textContent = '— (vincule um produto na entrega ou cadastre o preço no produto)';
                }
                if (nome) {
                    elNome.textContent = nome;
                    wrapNome.classList.remove('d-none');
                } else {
                    wrapNome.classList.add('d-none');
                }
            }

            function calcTotal() {
                const inputRepasseUnit = document.getElementById('valor_repasse_unitario');
                const inputQtd = document.getElementById('quantidade');
                const inputTotal = document.getElementById('valor_repasse');
                if (!inputRepasseUnit || !inputQtd || !inputTotal) return;
                const u = parseFloat((inputRepasseUnit.value || '').replace(',', '.'));
                const q = parseFloat((inputQtd.value || '').replace(',', '.'));
                if (Number.isNaN(u) || Number.isNaN(q)) {
                    inputTotal.value = '';
                    return;
                }
                inputTotal.value = (u * q).toFixed(2);
            }

            sel?.addEventListener('change', sync);
            sel?.addEventListener('change', calcTotal);
            document.getElementById('valor_repasse_unitario')?.addEventListener('input', calcTotal);
            document.getElementById('quantidade')?.addEventListener('input', calcTotal);
            sync();
            calcTotal();
        })();
    </script>
@endpush
