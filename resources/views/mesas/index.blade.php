@extends('layouts.empresa')

@section('title', 'Mapa de mesas')

@push('styles')
<style>
.vf-mesa-card { min-height: 140px; border-radius: 12px; border: 2px solid transparent; transition: transform .12s ease; }
.vf-mesa-card:active { transform: scale(0.98); }
.vf-mesa--livre { border-color: #19875433; background: linear-gradient(145deg, #d1e7dd, #fff); }
.vf-mesa--ocupada { border-color: #ffc10755; background: linear-gradient(145deg, #fff3cd, #fff); }
.vf-mesa--aguardando_pedido { border-color: #0dcaf055; background: linear-gradient(145deg, #cff4fc, #fff); }
.vf-mesa--pedido_enviado { border-color: #0d6efd33; background: linear-gradient(145deg, #cfe2ff, #fff); }
.vf-mesa--em_preparo { border-color: #fd7e1433; background: linear-gradient(145deg, #ffe5d0, #fff); }
.vf-mesa--conta_solicitada { border-color: #6f42c133; background: linear-gradient(145deg, #e2d9f3, #fff); }
.vf-mesa--fechada { border-color: #6c757d33; background: linear-gradient(145deg, #e9ecef, #fff); }
.vf-mesa-num { font-size: 1.75rem; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <p class="text-muted mb-0">Toque nos botões — otimizado para tablet e celular do salão.</p>
    @if (auth()->user()->temPainelEmpresaCompleto())
        <a href="{{ route('empresa.mesas.configuracoes') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-sliders me-1"></i>Configurações
        </a>
    @endif
</div>

<div class="row g-3">
    @forelse ($mesas as $mesa)
        @php
            $st = $mesa->status->value;
            $comanda = $mesa->comandaAberta;
        @endphp
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card vf-mesa-card vf-mesa--{{ $st }} shadow-sm h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="vf-mesa-num">#{{ $mesa->numero }}</div>
                            @if ($mesa->nome)
                                <div class="small text-muted">{{ $mesa->nome }}</div>
                            @endif
                        </div>
                        <span class="badge rounded-pill bg-dark bg-opacity-75">{{ $mesa->status->label() }}</span>
                    </div>
                    @if ($mesa->localizacao)
                        <div class="small mt-1"><i class="bi bi-geo-alt"></i> {{ $mesa->localizacao }}</div>
                    @endif
                    <div class="small text-muted mt-1">Capacidade: {{ $mesa->capacidade }}</div>

                    <div class="mt-auto pt-3 d-grid gap-2">
                        @if ($comanda)
                            <a href="{{ route('empresa.comandas.show', $comanda) }}" class="btn btn-primary btn-lg">
                                <i class="bi bi-journal-text me-1"></i>Ver comanda
                            </a>
                            <form method="post" action="{{ route('empresa.mesas.solicitar-conta', $mesa) }}" class="d-grid">
                                @csrf
                                <button type="submit" class="btn btn-outline-dark btn-lg">Solicitar conta</button>
                            </form>
                            @if (auth()->user()->role !== \App\Models\User::ROLE_ATENDENTE)
                                <a href="{{ route('empresa.mesas.fechamento.redirect-mesa', $mesa) }}" class="btn btn-success btn-lg">Fechar mesa</a>
                            @endif
                        @else
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#modalAbrir{{ $mesa->id }}">
                                <i class="bi bi-unlock me-1"></i>Abrir mesa
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalAbrir{{ $mesa->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" action="{{ route('empresa.mesas.abrir', $mesa) }}">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title h5">Abrir mesa #{{ $mesa->numero }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="form-label">Cliente (opcional)</label>
                                <input type="text" name="cliente_nome" class="form-control" maxlength="160" placeholder="Nome na comanda">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Documento (opcional)</label>
                                <input type="text" name="cliente_documento" class="form-control" maxlength="32" placeholder="CPF / CNPJ">
                            </div>
                            @if (auth()->user()->temPainelEmpresaCompleto())
                                <div class="mb-2">
                                    <label class="form-label">Garçom</label>
                                    <select name="garcom_id" class="form-select">
                                        <option value="">—</option>
                                        @foreach (\App\Models\User::query()->where('empresa_id', auth()->user()->empresa_id)->orderBy('name')->get() as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="garcom_id" value="{{ auth()->id() }}">
                                <p class="small text-muted mb-2 mb-md-0">
                                    <i class="bi bi-person-check me-1"></i>Atendente: <strong>{{ auth()->user()->name }}</strong>
                                </p>
                            @endif
                            <div class="mb-0">
                                <label class="form-label">Observação</label>
                                <textarea name="observacao" class="form-control" rows="2" maxlength="2000"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">Abrir comanda</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info mb-0">
                Nenhuma mesa cadastrada.
                @if (auth()->user()->temPainelEmpresaCompleto())
                    Acesse <a href="{{ route('empresa.mesas.configuracoes') }}">Configurações de mesas</a> para criar.
                @else
                    Peça ao gestor para cadastrar as mesas.
                @endif
            </div>
        </div>
    @endforelse
</div>
@endsection
