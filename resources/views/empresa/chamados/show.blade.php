@extends('layouts.empresa')

@section('title', 'Chamado #'.$ticket->id)

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Meus chamados', 'url' => route('empresa.chamados.index')],
        ['label' => '#'.$ticket->id, 'url' => route('empresa.chamados.show', ['suporteTicket' => $ticket])],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

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
                        <h1 class="h5 fw-bold mb-1">#{{ $ticket->id }} — {{ $ticket->assunto }}</h1>
                        <p class="small text-muted mb-0">Atualizado em {{ $ticket->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="vf-badge bg-{{ $pTone }}-subtle text-{{ $pTone }}">{{ \App\Models\SuporteTicket::prioridades()[$ticket->prioridade] }}</span>
                        <span class="vf-badge bg-{{ $sTone }}-subtle text-{{ $sTone }}">{{ \App\Models\SuporteTicket::statusRotulos()[$ticket->status] }}</span>
                    </div>
                </div>
                <label class="form-label small text-muted">Descrição inicial</label>
                <div class="small mb-0" style="white-space: pre-wrap;">{{ $ticket->descricao !== null && $ticket->descricao !== '' ? $ticket->descricao : '—' }}</div>
            </div>

            <div class="vf-card p-4 mb-3">
                <h2 class="h6 fw-bold mb-3">Conversa</h2>
                @forelse ($ticket->mensagens as $msg)
                    <div class="border-bottom pb-3 mb-3 @if($loop->last) border-0 pb-0 mb-0 @endif">
                        <div class="d-flex justify-content-between flex-wrap gap-1 mb-1">
                            <span class="small fw-semibold">{{ $msg->user?->name ?? 'Usuário' }}</span>
                            <span class="small text-muted">{{ $msg->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="small" style="white-space: pre-wrap;">{{ $msg->corpo }}</div>
                    </div>
                @empty
                    <p class="small text-muted mb-0">Nenhuma mensagem ainda além da descrição inicial.</p>
                @endforelse
            </div>

            <div class="vf-card p-4">
                <h2 class="h6 fw-bold mb-3">Nova mensagem</h2>
                <p class="small text-muted mb-3">Envie atualizações ou respostas. Se o chamado estiver resolvido ou fechado, ele volta para <strong>Aguardando</strong> automaticamente.</p>
                <form action="{{ route('empresa.chamados.mensagens.store', ['suporteTicket' => $ticket]) }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <textarea class="form-control @error('corpo') is-invalid @enderror" name="corpo" rows="4" required maxlength="5000" placeholder="Escreva sua mensagem…">{{ old('corpo') }}</textarea>
                        @error('corpo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Enviar mensagem</button>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="vf-card p-3">
                <a href="{{ route('empresa.chamados.index') }}" class="btn btn-light border w-100">Voltar à lista</a>
            </div>
        </div>
    </div>
@endsection
