@extends('layouts.empresa')

@section('title', $fiado->exists ? 'Editar fiado' : 'Novo fiado')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiados', 'url' => route('empresa.venda-externa.fiados')],
        ['label' => $fiado->exists ? 'Editar #'.$fiado->id : 'Novo', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 38rem;">
        <h2 class="h5 fw-bold mb-4">{{ $fiado->exists ? 'Editar fiado #'.$fiado->id : 'Novo fiado' }}</h2>
        <form action="{{ $fiado->exists ? route('empresa.venda-externa.fiados.update', $fiado) : route('empresa.venda-externa.fiados.store') }}" method="post">
            @csrf
            @if ($fiado->exists)
                @method('PUT')
            @endif
            <div class="mb-3">
                <label class="form-label" for="contraparte">Contraparte / devedor</label>
                <input type="text" class="form-control @error('contraparte') is-invalid @enderror" id="contraparte" name="contraparte" value="{{ old('contraparte', $fiado->contraparte) }}" placeholder="Ex.: Mercado Z, cliente B2B">
                @error('contraparte')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="ve_ponto_id">Ponto (opcional)</label>
                <select class="form-select @error('ve_ponto_id') is-invalid @enderror" id="ve_ponto_id" name="ve_ponto_id">
                    <option value="">— Nenhum —</option>
                    @foreach ($pontos as $pt)
                        <option value="{{ $pt->id }}" @selected((string) old('ve_ponto_id', $fiado->ve_ponto_id) === (string) $pt->id)>{{ $pt->nome }}</option>
                    @endforeach
                </select>
                @error('ve_ponto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="descricao">Descrição</label>
                <textarea class="form-control @error('descricao') is-invalid @enderror" id="descricao" name="descricao" rows="2" required>{{ old('descricao', $fiado->descricao) }}</textarea>
                @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="valor">Valor (R$)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control @error('valor') is-invalid @enderror" id="valor" name="valor" value="{{ old('valor', $fiado->valor) }}" required>
                    @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="vencimento">Vencimento</label>
                    <input type="date" class="form-control @error('vencimento') is-invalid @enderror" id="vencimento" name="vencimento" value="{{ old('vencimento', $fiado->vencimento?->format('Y-m-d')) }}">
                    @error('vencimento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (\App\Models\VeFiado::rotulosStatus() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(old('status', $fiado->status ?: \App\Models\VeFiado::STATUS_ABERTO) === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                @if ($fiado->exists)
                    <a href="{{ route('empresa.venda-externa.fiados.show', $fiado) }}" class="btn btn-outline-secondary">Cancelar</a>
                @else
                    <a href="{{ route('empresa.venda-externa.fiados') }}" class="btn btn-outline-secondary">Cancelar</a>
                @endif
            </div>
        </form>
    </div>
@endsection
