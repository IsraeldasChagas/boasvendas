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
            <div class="mb-4">
                <label class="form-label">Módulos liberados para a empresa</label>
                <div class="border rounded p-3 bg-light" style="max-height: 14rem; overflow-y: auto;">
                    @forelse ($modulos as $m)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="modulo_ids[]" id="mod-{{ $m->id }}" value="{{ $m->id }}"
                                @checked(in_array((int) $m->id, array_map('intval', (array) old('modulo_ids', [])), true))>
                            <label class="form-check-label" for="mod-{{ $m->id }}">
                                {{ $m->nome }}
                                <span class="text-muted small">({{ \App\Models\Modulo::situacoes()[$m->situacao] ?? $m->situacao }})</span>
                            </label>
                        </div>
                    @empty
                        <div class="small text-muted">Nenhum módulo cadastrado. Cadastre em <a href="{{ route('admin.modulos.index') }}">Módulos</a>.</div>
                    @endforelse
                </div>
                @error('modulo_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('modulo_ids.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="form-text">Isso substitui o “resumo” manual e será usado para liberar acesso (próximo passo).</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="cliente_desde">Cliente desde</label>
                <input type="date" class="form-control @error('cliente_desde') is-invalid @enderror" id="cliente_desde" name="cliente_desde" value="{{ old('cliente_desde') }}">
                @error('cliente_desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <hr class="my-4">

            <h3 class="h6 fw-bold mb-3">Usuário administrador da empresa</h3>
            <p class="small text-muted mb-3">Esse usuário será criado com perfil <strong>Gestor</strong> e poderá acessar o painel da empresa.</p>
            <div class="mb-3">
                <label class="form-label" for="admin_name">Nome do administrador</label>
                <input type="text" class="form-control @error('admin_name') is-invalid @enderror" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
                @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="admin_email">E-mail do administrador</label>
                <input type="email" class="form-control @error('admin_email') is-invalid @enderror" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required>
                @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <label class="form-label" for="admin_password">Senha</label>
                    <input type="password" class="form-control @error('admin_password') is-invalid @enderror" id="admin_password" name="admin_password" required>
                    @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="admin_password_confirmation">Confirmar senha</label>
                    <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" required>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('admin.empresas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
