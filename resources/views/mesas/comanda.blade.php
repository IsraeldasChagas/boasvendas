@extends('layouts.empresa')

@section('title')
Comanda — Mesa #{{ $comanda->mesa?->numero }}
@endsection

@section('content')
@php
    $fechada = in_array($comanda->status->value, ['fechada', 'cancelada'], true);
@endphp

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Mesa e responsável</div>
            <div class="card-body">
                <p class="mb-1"><strong>Mesa:</strong> #{{ $comanda->mesa?->numero }} {{ $comanda->mesa?->nome }}</p>
                <p class="mb-1"><strong>Status comanda:</strong> <span class="badge bg-dark">{{ $comanda->status->label() }}</span></p>
                @if (! $fechada)
                    <form method="post" action="{{ route('empresa.comandas.cabecalho', $comanda) }}" class="mt-3">
                        @csrf
                        @method('PUT')
                        <div class="mb-2">
                            <label class="form-label">Cliente</label>
                            <input type="text" name="cliente_nome" value="{{ old('cliente_nome', $comanda->cliente_nome) }}" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Documento</label>
                            <input type="text" name="cliente_documento" value="{{ old('cliente_documento', $comanda->cliente_documento) }}" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Garçom</label>
                            <select name="garcom_id" class="form-select">
                                <option value="">—</option>
                                @foreach ($garcons as $u)
                                    <option value="{{ $u->id }}" @selected(old('garcom_id', $comanda->garcom_id) == $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Observação</label>
                            <textarea name="observacao" class="form-control" rows="2">{{ old('observacao', $comanda->observacao) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-lg">Salvar dados</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Adicionar produto</div>
            <div class="card-body">
                @if ($fechada)
                    <p class="text-muted mb-0">Comanda encerrada — somente consulta.</p>
                @else
                    <form method="post" action="{{ route('empresa.comandas.itens.store', $comanda) }}" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Produto</label>
                            <select name="produto_id" class="form-select" required>
                                <option value="">Selecione…</option>
                                @foreach ($produtos as $p)
                                    <option value="{{ $p->id }}">{{ $p->nome }} — R$ {{ number_format((float) $p->preco, 2, ',', '.') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Qtd</label>
                            <input type="number" name="quantidade" value="1" min="1" class="form-control" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Setor</label>
                            <select name="setor_destino" class="form-select" required>
                                @foreach ($setores as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-10">
                            <label class="form-label">Obs. do item</label>
                            <input type="text" name="observacao" class="form-control" maxlength="500" placeholder="Sem cebola…">
                        </div>
                        <div class="col-12 col-md-2 d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Adicionar</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold">Itens</span>
                @if (! $fechada)
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="{{ route('empresa.comandas.enviar-cozinha', $comanda) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">Enviar pendentes</button>
                        </form>
                        <a href="{{ route('empresa.comandas.pre-conta', $comanda) }}" target="_blank" class="btn btn-outline-secondary">Pré-conta</a>
                        @if (auth()->user()->role !== \App\Models\User::ROLE_ATENDENTE)
                            <a href="{{ route('empresa.mesas.fechamento.show', $comanda) }}" class="btn btn-outline-success">Fechamento</a>
                        @endif
                    </div>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @forelse ($comanda->itens as $item)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <strong>{{ $item->nome_produto }}</strong>
                                <span class="text-muted">× {{ $item->quantidade }}</span>
                                <div class="small"><span class="badge bg-light text-dark">{{ $item->setor_destino->label() }}</span> · {{ $item->status->label() }}</div>
                                @if ($item->observacao)
                                    <div class="small text-muted">{{ $item->observacao }}</div>
                                @endif
                            </div>
                            <div class="text-end">
                                <div>R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}</div>
                                @if (! $fechada && $item->status === \App\Enums\Mesas\ComandaItemStatus::Pendente)
                                    <form method="post" action="{{ route('empresa.comandas.itens.destroy', [$comanda, $item]) }}" class="mt-1" onsubmit="return confirm('Remover este item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">Nenhum item lançado.</div>
                @endforelse
            </div>
            <div class="card-footer">
                <div class="row small">
                    <div class="col-6">Subtotal</div><div class="col-6 text-end">R$ {{ number_format((float) $comanda->subtotal, 2, ',', '.') }}</div>
                    <div class="col-6">Taxa serviço</div><div class="col-6 text-end">R$ {{ number_format((float) $comanda->taxa_servico, 2, ',', '.') }}</div>
                    <div class="col-6">Desconto</div><div class="col-6 text-end">R$ {{ number_format((float) $comanda->desconto, 2, ',', '.') }}</div>
                    <div class="col-6 fw-bold pt-2">Total</div><div class="col-6 text-end fw-bold pt-2 fs-5">R$ {{ number_format((float) $comanda->total, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
