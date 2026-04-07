@extends('layouts.empresa')

@section('title', $ponto->exists ? 'Editar ponto' : 'Novo ponto')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Pontos', 'url' => route('empresa.venda-externa.pontos')],
        ['label' => $ponto->exists ? 'Editar' : 'Novo', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 36rem;">
        <h2 class="h5 fw-bold mb-4">{{ $ponto->exists ? 'Editar ponto' : 'Novo ponto' }}</h2>
        <form action="{{ $ponto->exists ? route('empresa.venda-externa.pontos.update', $ponto) : route('empresa.venda-externa.pontos.store') }}" method="post">
            @csrf
            @if ($ponto->exists)
                @method('PUT')
            @endif
            <div class="mb-3">
                <label class="form-label" for="nome">Nome</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $ponto->nome) }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="regiao">Região / local</label>
                <input type="text" class="form-control @error('regiao') is-invalid @enderror" id="regiao" name="regiao" value="{{ old('regiao', $ponto->regiao) }}" placeholder="Ex.: Zona Sul, Centro">
                @error('regiao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (\App\Models\VePonto::rotulosStatus() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(old('status', $ponto->status ?: \App\Models\VePonto::STATUS_ATIVO) === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="proximo_acerto_em">Próximo acerto</label>
                    <input type="datetime-local" class="form-control @error('proximo_acerto_em') is-invalid @enderror" id="proximo_acerto_em" name="proximo_acerto_em" value="{{ old('proximo_acerto_em', $ponto->proximo_acerto_em?->format('Y-m-d\TH:i')) }}">
                    @error('proximo_acerto_em')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="ultimo_acerto_em">Último acerto</label>
                    <input type="datetime-local" class="form-control @error('ultimo_acerto_em') is-invalid @enderror" id="ultimo_acerto_em" name="ultimo_acerto_em" value="{{ old('ultimo_acerto_em', $ponto->ultimo_acerto_em?->format('Y-m-d\TH:i')) }}">
                    @error('ultimo_acerto_em')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <p class="small text-muted mb-4">Datas de acerto são opcionais e alimentam o dashboard de venda externa.</p>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresa.venda-externa.pontos') }}" class="btn btn-outline-secondary">Voltar</a>
            </div>
        </form>
        @if ($ponto->exists)
            <hr class="my-4">
            <form action="{{ route('empresa.venda-externa.pontos.destroy', $ponto) }}" method="post" onsubmit="return confirm('Excluir este ponto? Entregas e fiados vinculados ficarão sem ponto; vendas registradas serão apagadas.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Excluir ponto</button>
            </form>
        @endif
    </div>
@endsection
