@extends('layouts.publico')

@section('title', 'Acompanhar pedido — '.$empresa->nome)

@section('content')
    <div class="container" style="max-width:560px">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('publico.loja', ['slug' => $slug]) }}">Cardápio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pedido</li>
            </ol>
        </nav>
        <h1 class="h4 fw-bold mb-3">Acompanhar pedido</h1>
        <p class="small text-muted mb-3">Digite o código que você recebeu após confirmar a compra (ex.: <strong>BV-ABC123</strong>).</p>
        <div class="vf-card p-3 mb-4">
            <form action="{{ route('publico.acompanhar.buscar', ['slug' => $slug]) }}" method="post">
                @csrf
                <label class="form-label" for="codigo">Código do pedido</label>
                <div class="input-group">
                    <input type="text" class="form-control @error('codigo') is-invalid @enderror" id="codigo" name="codigo" value="{{ old('codigo') }}" placeholder="BV-XXXXXX" required maxlength="32" autocomplete="off">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    @error('codigo')<div class="invalid-feedback d-block w-100">{{ $message }}</div>@enderror
                </div>
            </form>
        </div>
    </div>
@endsection
