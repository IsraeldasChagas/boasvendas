@extends('layouts.empresa')

@section('title', 'Frete por CEP')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Configurações', 'url' => route('empresa.configuracoes.index')],
        ['label' => 'Frete por CEP', 'url' => route('empresa.loja-entrega-faixas.index')],
    ]])

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 fw-bold mb-0">Frete por CEP</h2>
            <p class="small text-muted mb-0">Cadastre faixas de CEP (8 dígitos) e o valor da entrega. Fora das faixas vale a <a href="{{ route('empresa.configuracoes.index') }}">taxa padrão da loja</a> (ou a global do sistema).</p>
        </div>
    </div>

    @if (\Illuminate\Support\Facades\Schema::hasColumn('empresas', 'loja_frete_modo') && ($empresa->lojaFreteModoEfetivo() === \App\Models\Empresa::LOJA_FRETE_PADRAO_UNICO))
        <div class="alert alert-info small mb-3">
            A loja está em <strong>“Só taxa padrão”</strong> nas <a href="{{ route('empresa.configuracoes.index') }}">configurações</a>. As faixas abaixo não entram no cálculo até você voltar ao modo <strong>Faixas de CEP</strong>.
        </div>
    @endif

    <div class="vf-card p-3 mb-4">
        <h3 class="h6 fw-bold mb-3">Nova faixa</h3>
        <form action="{{ route('empresa.loja-entrega-faixas.store') }}" method="post" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label small" for="cep_inicio">CEP inicial</label>
                <input type="text" class="form-control form-control-sm @error('cep_inicio') is-invalid @enderror" id="cep_inicio" name="cep_inicio" value="{{ old('cep_inicio') }}" placeholder="01000000" maxlength="12" required>
                @error('cep_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label small" for="cep_fim">CEP final</label>
                <input type="text" class="form-control form-control-sm @error('cep_fim') is-invalid @enderror" id="cep_fim" name="cep_fim" value="{{ old('cep_fim') }}" placeholder="01999999" maxlength="12" required>
                @error('cep_fim')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-2">
                <label class="form-label small" for="valor_taxa">Taxa (R$)</label>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm @error('valor_taxa') is-invalid @enderror" id="valor_taxa" name="valor_taxa" value="{{ old('valor_taxa') }}" required>
                @error('valor_taxa')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label small" for="nome_regiao">Nome <span class="text-muted">(opcional)</span></label>
                <input type="text" class="form-control form-control-sm @error('nome_regiao') is-invalid @enderror" id="nome_regiao" name="nome_regiao" value="{{ old('nome_regiao') }}" maxlength="120" placeholder="Ex.: Centro">
                @error('nome_regiao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">Adicionar</button>
            </div>
        </form>
        <p class="small text-muted mb-0 mt-2">Use apenas números ou com hífen; o sistema normaliza para 8 dígitos. Ex.: de <code>01000000</code> a <code>01999999</code> cobre CEPs de 01000-000 a 01999-999.</p>
    </div>

    <div class="vf-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0 vf-table">
                <thead>
                    <tr>
                        <th>CEP inicial</th>
                        <th>CEP final</th>
                        <th>Taxa (R$)</th>
                        <th>Região</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faixas as $f)
                        <tr>
                            <td class="font-monospace small">{{ $f->cep_inicio }}</td>
                            <td class="font-monospace small">{{ $f->cep_fim }}</td>
                            <td>R$ {{ number_format((float) $f->valor_taxa, 2, ',', '.') }}</td>
                            <td class="small">{{ $f->nome_regiao ?: '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('empresa.loja-entrega-faixas.destroy', $f) }}" method="post" class="d-inline" onsubmit="return confirm('Remover esta faixa?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Nenhuma faixa cadastrada. Enquanto isso, todos os CEPs usam a taxa padrão.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
