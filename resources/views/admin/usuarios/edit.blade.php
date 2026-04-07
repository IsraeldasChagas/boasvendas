@extends('layouts.admin')

@section('title', 'Editar usuário')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.usuarios.index') }}" class="small text-decoration-none">&larr; Voltar à lista</a>
    </div>
    <h2 class="h5 fw-bold mb-3">Editar usuário #{{ $user->id }}</h2>

    <p class="small text-muted mb-3">Master /admin: e-mail deve estar em <code>VENDAFFACIL_ADMIN_EMAILS</code> no <code>.env</code>.</p>

    <div class="vf-card p-4" style="max-width: 40rem;">
        <form action="{{ route('admin.usuarios.update', $user) }}" method="post">
            @csrf
            @method('put')
            <div class="mb-3">
                <label class="form-label" for="name">Nome</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Nova senha</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" placeholder="Deixe em branco para não alterar">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Confirmar nova senha</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label class="form-label" for="empresa_id">Empresa (opcional)</label>
                <select class="form-select @error('empresa_id') is-invalid @enderror" id="empresa_id" name="empresa_id">
                    <option value="">— Nenhuma —</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected(old('empresa_id', $user->empresa_id) == $empresa->id)>{{ $empresa->nome }}</option>
                    @endforeach
                </select>
                @error('empresa_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="role">Função na empresa</label>
                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                    @foreach (\App\Models\User::rolesEquipe() as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(old('role', $user->role) === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
