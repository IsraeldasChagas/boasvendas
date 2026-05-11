@extends('layouts.empresa')

@section('title', 'Meus entregadores')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Pedidos', 'url' => route('empresa.pedidos.index')],
        ['label' => 'Meus entregadores', 'url' => route('empresa.entregadores.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-1">Meus entregadores</h2>
            <p class="small text-muted mb-0">Quem entrega com você aparece na tela do pedido na <strong>ordem abaixo</strong> (menor número primeiro). Aí você chama pelo WhatsApp antes de apps ou terceiros.</p>
        </div>
        <a href="{{ route('empresa.entregadores.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Novo entregador</a>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        @if ($entregadores->isEmpty())
            <div class="p-4 text-center text-muted small">Nenhum entregador cadastrado. Use <strong>Novo entregador</strong> para adicionar foto, WhatsApp e dados da moto.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0 vf-table align-middle">
                    <thead>
                        <tr>
                            <th style="width:4rem"></th>
                            <th>Nome</th>
                            <th>Moto</th>
                            <th class="text-center">Ordem</th>
                            <th class="text-center">Ativo</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entregadores as $e)
                            <tr>
                                <td>
                                    @if ($e->urlFoto())
                                        <img src="{{ $e->urlFoto() }}" alt="" width="48" height="48" class="rounded border object-fit-cover" style="object-fit:cover">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted" style="width:48px;height:48px"><i class="bi bi-person"></i></span>
                                    @endif
                                </td>
                                <td class="fw-medium">{{ $e->nome }}<br><span class="small text-muted font-monospace">{{ $e->whatsapp }}</span></td>
                                <td class="small">{{ $e->rotuloMotoCurto() }}</td>
                                <td class="text-center">{{ $e->ordem }}</td>
                                <td class="text-center">@if ($e->ativo)<span class="vf-badge bg-success-subtle text-success">Sim</span>@else<span class="vf-badge bg-secondary-subtle text-secondary">Não</span>@endif</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('empresa.entregadores.edit', $e) }}" class="btn btn-outline-primary btn-sm">Editar</a>
                                    <form action="{{ route('empresa.entregadores.destroy', $e) }}" method="post" class="d-inline" onsubmit="return confirm('Remover este entregador?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
