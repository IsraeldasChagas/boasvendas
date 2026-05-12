@extends('layouts.empresa')

@section('title', 'Cartão fidelidade — '.$cliente->nome)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Clientes', 'url' => route('empresa.clientes.index')],
        ['label' => $cliente->nome, 'url' => route('empresa.clientes.edit', $cliente)],
        ['label' => 'Cartão fidelidade', 'url' => route('empresa.clientes.cartao-fidelidade.show', $cliente)],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 fw-bold mb-0">Cartão fidelidade</h2>
        <a href="{{ route('empresa.clientes.edit', $cliente) }}" class="btn btn-outline-secondary btn-sm">Voltar ao cliente</a>
    </div>

    @if (! $cartao)
        <div class="vf-card p-4" style="max-width: 32rem;">
            <p class="text-muted small mb-3">Gere um cartão com código único (ex.: VF-{{ date('Y') }}-1234) vinculado ao telefone deste cliente.</p>
            <form action="{{ route('empresa.clientes.cartao-fidelidade.gerar', $cliente) }}" method="post">
                @csrf
                <button type="submit" class="btn btn-primary">Gerar cartão fidelidade</button>
            </form>
        </div>
    @else
        <div class="vf-card p-4 mb-3" style="max-width: 36rem;">
            <dl class="row small mb-0">
                <dt class="col-sm-4">Nome</dt>
                <dd class="col-sm-8">{{ $cliente->nome }}</dd>
                <dt class="col-sm-4">Telefone / WhatsApp</dt>
                <dd class="col-sm-8">{{ $cliente->telefone ?: '—' }}</dd>
                <dt class="col-sm-4">Código fidelidade</dt>
                <dd class="col-sm-8 font-monospace fw-semibold">{{ $cartao->codigo_fidelidade }}</dd>
                <dt class="col-sm-4">Pontos atuais</dt>
                <dd class="col-sm-8 fw-semibold">{{ (int) ($cartao->pontos ?? 0) }}</dd>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">
                    @if ($cartao->estaAtivo())
                        <span class="vf-badge bg-success-subtle text-success">Ativo</span>
                    @else
                        <span class="vf-badge bg-secondary-subtle text-secondary">Inativo</span>
                    @endif
                </dd>
            </dl>
            <div class="mt-3 d-flex flex-wrap gap-2">
                @if ($waUrl)
                    <a href="{{ $waUrl }}" class="btn btn-success" target="_blank" rel="noopener">Enviar pelo WhatsApp</a>
                @else
                    <button type="button" class="btn btn-outline-secondary" disabled title="Cadastre telefone válido no cliente">Enviar pelo WhatsApp</button>
                @endif
                <a href="{{ route('empresa.fidelidade.cartoes.historico', $cartao) }}" class="btn btn-outline-secondary">Ver histórico de pontos</a>
            </div>
        </div>
    @endif
@endsection
