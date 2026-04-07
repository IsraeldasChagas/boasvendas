@extends('layouts.admin')

@section('title', 'Editar empresa')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.empresas.show', $empresa) }}" class="small text-decoration-none">&larr; Voltar aos detalhes</a>
    </div>
    <h2 class="h5 fw-bold mb-3">Editar: {{ $empresa->nome }}</h2>

    <div class="vf-card p-4" style="max-width: 40rem;">
        <form action="{{ route('admin.empresas.update', $empresa) }}" method="post">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label" for="nome">Nome da empresa</label>
                <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $empresa->nome) }}" required>
                @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="email_contato">E-mail de contato</label>
                <input type="email" class="form-control @error('email_contato') is-invalid @enderror" id="email_contato" name="email_contato" value="{{ old('email_contato', $empresa->email_contato) }}">
                @error('email_contato')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="cnpj">CNPJ</label>
                <input type="text" class="form-control @error('cnpj') is-invalid @enderror" id="cnpj" name="cnpj" value="{{ old('cnpj', $empresa->cnpj) }}">
                @error('cnpj')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="plano_id">Plano</label>
                <select class="form-select @error('plano_id') is-invalid @enderror" id="plano_id" name="plano_id">
                    <option value="">— Nenhum —</option>
                    @foreach ($planos as $plano)
                        <option value="{{ $plano->id }}" @selected(old('plano_id', $empresa->plano_id) == $plano->id)>{{ $plano->nome }}</option>
                    @endforeach
                </select>
                @error('plano_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (\App\Models\Empresa::statusRotulos() as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(old('status', $empresa->status) === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label">Telas do menu liberadas para a empresa</label>
                @php $selMenu = (array) old('menu_acessos', $empresa->menu_acessos ?? []); @endphp
                <div class="border rounded p-3 bg-light" style="max-height: 14rem; overflow-y: auto;">
                    @foreach (\App\Models\Empresa::telasMenuEmpresaRotulos() as $key => $rotulo)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="menu_acessos[]" id="menu-{{ $key }}" value="{{ $key }}"
                                @checked(in_array($key, $selMenu, true))>
                            <label class="form-check-label" for="menu-{{ $key }}">{{ $rotulo }}</label>
                        </div>
                    @endforeach
                </div>
                @error('menu_acessos')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('menu_acessos.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="cliente_desde">Cliente desde</label>
                <input type="date" class="form-control @error('cliente_desde') is-invalid @enderror" id="cliente_desde" name="cliente_desde" value="{{ old('cliente_desde', optional($empresa->cliente_desde)->format('Y-m-d')) }}">
                @error('cliente_desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Atualizar</button>
                <a href="{{ route('admin.empresas.show', $empresa) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
