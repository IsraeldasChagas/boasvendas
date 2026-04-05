@extends('layouts.empresa')

@section('title', $usuario->exists ? 'Editar usuário' : 'Novo usuário')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Usuários', 'url' => route('empresa.usuarios.index')],
        ['label' => $usuario->exists ? 'Editar' : 'Novo', 'url' => '#'],
    ]])

    <div class="vf-card p-4" style="max-width: 32rem;">
        <h1 class="h5 fw-bold mb-4">{{ $usuario->exists ? 'Editar usuário' : 'Novo usuário' }}</h1>

        <form action="{{ $usuario->exists ? route('empresa.usuarios.update', ['usuario' => $usuario]) : route('empresa.usuarios.store') }}" method="post">
            @csrf
            @if ($usuario->exists)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label class="form-label" for="name">Nome completo</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $usuario->name) }}" required maxlength="255" autofocus>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $usuario->email) }}" required maxlength="255" autocomplete="email">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="role">Perfil</label>
                <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                    @foreach (\App\Models\User::rolesEquipe() as $val => $rotulo)
                        <option value="{{ $val }}" @selected(old('role', $usuario->role ?? 'operador') === $val)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">{{ $usuario->exists ? 'Nova senha' : 'Senha' }}</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" @if(! $usuario->exists) required @endif>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if ($usuario->exists)
                    <p class="small text-muted mb-0 mt-1">Deixe em branco para manter a senha atual.</p>
                @endif
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">Confirmar senha</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @if(! $usuario->exists) required @endif>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">{{ $usuario->exists ? 'Salvar' : 'Cadastrar' }}</button>
                <a href="{{ route('empresa.usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
