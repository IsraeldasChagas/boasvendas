@extends('layouts.admin')

@section('title', 'Nova empresa')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.empresas.index') }}" class="small text-decoration-none">&larr; Voltar às empresas</a>
    </div>
    <h2 class="h5 fw-bold mb-3">Nova empresa</h2>

    <div class="vf-card p-4" style="max-width: 40rem;">
        <form action="{{ route('admin.empresas.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="nome">Nome da empresa</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome') }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="email_contato">E-mail de contato</label>
                <input type="email" class="form-control @error('email_contato') is-invalid @enderror" id="email_contato" name="email_contato" value="{{ old('email_contato') }}">
                @error('email_contato')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="cnpj">CNPJ</label>
                <input type="text" class="form-control @error('cnpj') is-invalid @enderror" id="cnpj" name="cnpj" value="{{ old('cnpj') }}" placeholder="Opcional">
                @error('cnpj')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="plano_id">Plano</label>
                <select class="form-select @error('plano_id') is-invalid @enderror" id="plano_id" name="plano_id">
                    <option value="">— Nenhum —</option>
                    @foreach ($planos as $plano)
                        <option value="{{ $plano->id }}" @selected(old('plano_id') == $plano->id)>{{ $plano->nome }}</option>
                    @endforeach
                </select>
                @error('plano_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (\App\Models\Empresa::statusRotulos() as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(old('status', 'ativa') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="modulos_resumo">Módulos (resumo)</label>
                <input type="text" class="form-control @error('modulos_resumo') is-invalid @enderror" id="modulos_resumo" name="modulos_resumo" value="{{ old('modulos_resumo') }}" placeholder="Ex.: VE + Delivery">
                @error('modulos_resumo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="cliente_desde">Cliente desde</label>
                <input type="date" class="form-control @error('cliente_desde') is-invalid @enderror" id="cliente_desde" name="cliente_desde" value="{{ old('cliente_desde') }}">
                @error('cliente_desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('admin.empresas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
