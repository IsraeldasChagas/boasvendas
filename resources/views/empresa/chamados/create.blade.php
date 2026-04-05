@extends('layouts.empresa')

@section('title', 'Novo chamado')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Meus chamados', 'url' => route('empresa.chamados.index')],
        ['label' => 'Novo', 'url' => route('empresa.chamados.create')],
    ]])

    <div class="vf-card p-4" style="max-width: 40rem;">
        <h1 class="h5 fw-bold mb-1">Novo chamado</h1>
        <p class="small text-muted mb-4">{{ $empresa->nome }}</p>

        <form action="{{ route('empresa.chamados.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="assunto">Assunto</label>
                <input type="text" class="form-control @error('assunto') is-invalid @enderror" id="assunto" name="assunto" value="{{ old('assunto') }}" required maxlength="255" autofocus>
                @error('assunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="prioridade">Prioridade</label>
                <select class="form-select @error('prioridade') is-invalid @enderror" id="prioridade" name="prioridade" required>
                    @foreach (\App\Models\SuporteTicket::prioridades() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(old('prioridade', 'media') === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('prioridade')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="descricao">Descrição</label>
                <textarea class="form-control @error('descricao') is-invalid @enderror" id="descricao" name="descricao" rows="5" placeholder="Descreva o problema ou a dúvida.">{{ old('descricao') }}</textarea>
                @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Enviar chamado</button>
                <a href="{{ route('empresa.chamados.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
