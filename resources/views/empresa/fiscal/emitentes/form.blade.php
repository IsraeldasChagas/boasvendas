@extends('layouts.empresa')

@section('title', $emitente->exists ? 'Fiscal — Editar emitente' : 'Fiscal — Novo emitente')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Fiscal', 'url' => route('empresa.fiscal.dashboard')],
        ['label' => 'Emitentes', 'url' => route('empresa.fiscal.emitentes.index')],
        ['label' => $emitente->exists ? 'Editar' : 'Novo', 'url' => '#'],
    ]])

    <div class="mb-3">
        <h2 class="h4 fw-bold mb-1">{{ $emitente->exists ? 'Editar emitente fiscal' : 'Cadastrar emitente fiscal' }}</h2>
        <p class="text-muted small mb-0">Dados usados na nota e enviados ao fisco. Eles podem ser diferentes dos dados comerciais da loja.</p>
    </div>

    <form method="post" style="max-width: 58rem;"
          action="{{ $emitente->exists ? route('empresa.fiscal.emitentes.update', $emitente) : route('empresa.fiscal.emitentes.store') }}">
        @csrf
        @if ($emitente->exists)
            @method('PUT')
        @endif

        @include('partials.empresa.fiscal-emitente-form')

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Salvar cadastro fiscal</button>
            <a href="{{ route('empresa.fiscal.emitentes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
@endsection
