@extends('layouts.admin')

@section('title', 'Ticket #'.$ticket->id)

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Suporte', 'url' => route('admin.suporte.index')],
        ['label' => '#'.$ticket->id, 'url' => route('admin.suporte.show', $ticket)],
    ]])

    @php
        $pTone = match ($ticket->prioridade) {
            'alta' => 'danger',
            'media' => 'warning',
            default => 'secondary',
        };
        $sTone = match ($ticket->status) {
            'aberto' => 'info',
            'aguardando' => 'secondary',
            'em_andamento' => 'primary',
            'resolvido' => 'success',
            default => 'dark',
        };
    @endphp

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-4 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h4 fw-bold mb-1">#{{ $ticket->id }} — {{ $ticket->assunto }}</h2>
                        <p class="text-muted small mb-0">
                            Empresa: <strong>{{ $ticket->empresa?->nome ?? '—' }}</strong>
                            · Atualizado em {{ $ticket->updated_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="vf-badge bg-{{ $pTone }}-subtle text-{{ $pTone }}">{{ \App\Models\SuporteTicket::prioridades()[$ticket->prioridade] }}</span>
                        <span class="vf-badge bg-{{ $sTone }}-subtle text-{{ $sTone }}">{{ \App\Models\SuporteTicket::statusRotulos()[$ticket->status] }}</span>
                    </div>
                </div>
                <label class="form-label small text-muted">Descrição</label>
                <div class="small" style="white-space: pre-wrap;">{{ $ticket->descricao !== null && $ticket->descricao !== '' ? $ticket->descricao : '—' }}</div>
            </div>

            @if ($ticket->mensagens->isNotEmpty())
                <div class="vf-card p-4">
                    <h3 class="h6 fw-bold mb-3">Mensagens</h3>
                    @foreach ($ticket->mensagens as $msg)
                        <div class="border-bottom pb-3 mb-3 @if($loop->last) border-0 pb-0 mb-0 @endif">
                            <div class="d-flex justify-content-between flex-wrap gap-1 mb-1">
                                <span class="small fw-semibold">{{ $msg->user?->name ?? 'Usuário' }}</span>
                                <span class="small text-muted">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="small" style="white-space: pre-wrap;">{{ $msg->corpo }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3 mb-3">
                <h3 class="h6 fw-bold mb-2">Ações</h3>
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.suporte.edit', $ticket) }}" class="btn btn-primary btn-sm">Editar ticket</a>
                </div>
                <form action="{{ route('admin.suporte.destroy', $ticket) }}" method="post" class="mt-3"
                      onsubmit="return confirm('Excluir este ticket?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Excluir</button>
                </form>
            </div>
            <a href="{{ route('admin.suporte.index') }}" class="btn btn-light border w-100">Voltar à lista</a>
        </div>
    </div>
@endsection
