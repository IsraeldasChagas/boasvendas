@extends('layouts.admin')

@section('title', 'Nova assinatura')

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.assinaturas.index') }}" class="small text-decoration-none">&larr; Voltar às assinaturas</a>
    </div>
    <h2 class="h5 fw-bold mb-3">Nova assinatura</h2>

    <div class="vf-card p-4" style="max-width: 40rem;">
        <form action="{{ route('admin.assinaturas.store') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="empresa_nome">Nome da empresa</label>
                <input type="text" class="form-control @error('empresa_nome') is-invalid @enderror" id="empresa_nome" name="empresa_nome" value="{{ old('empresa_nome') }}" required>
                @error('empresa_nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="plano_id">Plano</label>
                <select class="form-select @error('plano_id') is-invalid @enderror" id="plano_id" name="plano_id">
                    <option value="">— Nenhum —</option>
                    @foreach ($planos as $plano)
                        <option value="{{ $plano->id }}" @selected(old('plano_id') == $plano->id)>
                            {{ $plano->nome }} (R$ {{ number_format((float) $plano->preco_mensal, 2, ',', '.') }}/mês)
                        </option>
                    @endforeach
                </select>
                @error('plano_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="valor_mensal">Valor mensal (R$)</label>
                <input type="number" class="form-control @error('valor_mensal') is-invalid @enderror" id="valor_mensal" name="valor_mensal" value="{{ old('valor_mensal') }}" min="0" step="0.01" required>
                @error('valor_mensal')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="proxima_cobranca">Próxima cobrança</label>
                <input type="date" class="form-control @error('proxima_cobranca') is-invalid @enderror" id="proxima_cobranca" name="proxima_cobranca" value="{{ old('proxima_cobranca') }}" required>
                @error('proxima_cobranca')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="gateway">Gateway / observação</label>
                <input type="text" class="form-control @error('gateway') is-invalid @enderror" id="gateway" name="gateway" value="{{ old('gateway') }}" placeholder="Ex.: Stripe, Asaas…">
                @error('gateway')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach (\App\Models\Assinatura::statusRotulos() as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(old('status', 'pendente') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('admin.assinaturas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
