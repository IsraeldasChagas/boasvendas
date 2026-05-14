@extends('layouts.empresa')

@section('title', 'Configurações de mesas')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Padrões do módulo</div>
            <div class="card-body">
                <form method="post" action="{{ route('empresa.mesas.configuracoes.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Taxa de serviço padrão (%)</label>
                        <input type="number" step="0.01" name="taxa_servico_padrao_percent" value="{{ old('taxa_servico_padrao_percent', $config->taxa_servico_padrao_percent) }}" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="exigir_garcom_abertura" value="1" id="exg" @checked(old('exigir_garcom_abertura', $config->exigir_garcom_abertura))>
                        <label class="form-check-label" for="exg">Exigir garçom ao abrir mesa</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header fw-semibold">Nova mesa</div>
            <div class="card-body">
                <form method="post" action="{{ route('empresa.mesas.store') }}" class="row g-2">
                    @csrf
                    <div class="col-4 col-md-3">
                        <label class="form-label">Número</label>
                        <input name="numero" class="form-control" required maxlength="32">
                    </div>
                    <div class="col-8 col-md-4">
                        <label class="form-label">Nome</label>
                        <input name="nome" class="form-control" maxlength="120">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Capacidade</label>
                        <input type="number" name="capacidade" value="4" min="1" class="form-control">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Localização</label>
                        <input name="localizacao" class="form-control" maxlength="120" placeholder="Salão, varanda…">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Cadastrar mesa</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Mesas cadastradas</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Cap.</th>
                            <th>Local</th>
                            <th>Ativo</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mesas as $m)
                            <tr>
                                <td colspan="6" class="p-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 p-2 border-bottom">
                                        <form method="post" action="{{ route('empresa.mesas.update', $m) }}" class="row g-1 flex-grow-1 align-items-end m-0">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small mb-0">Número</label>
                                                <input name="numero" value="{{ $m->numero }}" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="form-label small mb-0">Nome</label>
                                                <input name="nome" value="{{ $m->nome }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-4 col-md-2">
                                                <label class="form-label small mb-0">Cap.</label>
                                                <input type="number" name="capacidade" value="{{ $m->capacidade }}" class="form-control form-control-sm" min="1">
                                            </div>
                                            <div class="col-8 col-md-3">
                                                <label class="form-label small mb-0">Local</label>
                                                <input name="localizacao" value="{{ $m->localizacao }}" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-6 col-md-2">
                                                <label class="form-label small mb-0">Ativo</label>
                                                <select name="ativo" class="form-select form-select-sm">
                                                    <option value="1" @selected($m->ativo)>Sim</option>
                                                    <option value="0" @selected(! $m->ativo)>Não</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-1 text-end">
                                                <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                                            </div>
                                        </form>
                                        <form method="post" action="{{ route('empresa.mesas.destroy', $m) }}" onsubmit="return confirm('Excluir mesa {{ $m->numero }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
