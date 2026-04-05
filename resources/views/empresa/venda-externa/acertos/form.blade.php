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
                <label class="form-label" for="ve_ponto_id">Ponto</label>
                <select class="form-select @error('ve_ponto_id') is-invalid @enderror" id="ve_ponto_id" name="ve_ponto_id" required>
                    <option value="">Selecione…</option>
                    @foreach ($pontos as $pt)
                        <option value="{{ $pt->id }}" @selected((string) old('ve_ponto_id', $acerto->ve_ponto_id) === (string) $pt->id)>{{ $pt->nome }}</option>
                    @endforeach
                </select>
                @error('ve_ponto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="ve_remessa_id">Remessa (opcional)</label>
                <select class="form-select @error('ve_remessa_id') is-invalid @enderror" id="ve_remessa_id" name="ve_remessa_id">
                    <option value="">— Nenhuma —</option>
                    @foreach ($remessas as $rm)
                        <option value="{{ $rm->id }}" @selected((string) old('ve_remessa_id', $acerto->ve_remessa_id) === (string) $rm->id)>
                            R-{{ $rm->id }} — {{ $rm->tituloExibicao() }}
                        </option>
                    @endforeach
                </select>
                @error('ve_remessa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <label class="form-label" for="valor_vendas">Vendas (R$)</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('valor_vendas') is-invalid @enderror" id="valor_vendas" name="valor_vendas" value="{{ old('valor_vendas', $acerto->valor_vendas) }}">
                    @error('valor_vendas')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="valor_repasse">Repasse (R$)</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('valor_repasse') is-invalid @enderror" id="valor_repasse" name="valor_repasse" value="{{ old('valor_repasse', $acerto->valor_repasse) }}">
                    @error('valor_repasse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
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
