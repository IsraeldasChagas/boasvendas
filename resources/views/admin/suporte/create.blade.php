@extends('layouts.admin')

@section('title', 'Novo ticket')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.suporte.index') }}" class="small text-decoration-none">&larr; Voltar ao suporte</a>
    </div>
    <h2 class="h5 fw-bold mb-3">Novo ticket</h2>

    <div class="vf-card p-4" style="max-width: 42rem;">
        <form action="{{ route('admin.suporte.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="empresa_id">Empresa</label>
                <select class="form-select @error('empresa_id') is-invalid @enderror" id="empresa_id" name="empresa_id">
                    <option value="">— Não informada —</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected(old('empresa_id') == $empresa->id)>{{ $empresa->nome }}</option>
                    @endforeach
                </select>
                @error('empresa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="assunto">Assunto</label>
                <input type="text" class="form-control @error('assunto') is-invalid @enderror" id="assunto" name="assunto" value="{{ old('assunto') }}" required>
                @error('assunto')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="descricao">Descrição</label>
                <textarea class="form-control @error('descricao') is-invalid @enderror" id="descricao" name="descricao" rows="5">{{ old('descricao') }}</textarea>
                @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" for="prioridade">Prioridade</label>
                    <select class="form-select @error('prioridade') is-invalid @enderror" id="prioridade" name="prioridade" required>
                        @foreach (\App\Models\SuporteTicket::prioridades() as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(old('prioridade', 'media') === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    @error('prioridade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        @foreach (\App\Models\SuporteTicket::statusRotulos() as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected(old('status', 'aberto') === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('admin.suporte.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
