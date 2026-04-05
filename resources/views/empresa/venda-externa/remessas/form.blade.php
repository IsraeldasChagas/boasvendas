@extends('layouts.empresa')

@section('title', $remessa->exists ? 'Editar remessa' : 'Nova remessa')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Remessas', 'url' => route('empresa.venda-externa.remessas.index')],
        ['label' => $remessa->exists ? 'Editar #'.$remessa->id : 'Nova', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 36rem;">
        <h2 class="h5 fw-bold mb-4">{{ $remessa->exists ? 'Editar remessa #'.$remessa->id : 'Nova remessa' }}</h2>
        <form action="{{ $remessa->exists ? route('empresa.venda-externa.remessas.update', $remessa) : route('empresa.venda-externa.remessas.store') }}" method="post">
            @csrf
            @if ($remessa->exists)
                @method('PUT')
            @endif
            <div class="mb-3">
                <label class="form-label" for="titulo">Título / referência</label>
                <input type="text" class="form-control @error('titulo') is-invalid @enderror" id="titulo" name="titulo" value="{{ old('titulo', $remessa->titulo) }}" placeholder="Ex.: Mix salgados, Reposição semanal">
                @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="ve_ponto_id">Ponto (opcional)</label>
                <select class="form-select @error('ve_ponto_id') is-invalid @enderror" id="ve_ponto_id" name="ve_ponto_id">
                    <option value="">— Nenhum —</option>
                    @foreach ($pontos as $pt)
                        <option value="{{ $pt->id }}" @selected((string) old('ve_ponto_id', $remessa->ve_ponto_id) === (string) $pt->id)>{{ $pt->nome }}@if ($pt->regiao) · {{ $pt->regiao }}@endif</option>
                    @endforeach
                </select>
                @error('ve_ponto_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (\App\Models\VeRemessa::rotulosStatus() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(old('status', $remessa->status ?: \App\Models\VeRemessa::STATUS_PREPARACAO) === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                @if ($remessa->exists)
                    <a href="{{ route('empresa.venda-externa.remessas.show', $remessa) }}" class="btn btn-outline-secondary">Cancelar</a>
                @else
                    <a href="{{ route('empresa.venda-externa.remessas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                @endif
            </div>
        </form>
    </div>
@endsection
