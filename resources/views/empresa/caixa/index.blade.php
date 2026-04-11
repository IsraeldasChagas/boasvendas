@extends('layouts.empresa')

@section('title', 'Caixa')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
        ['label' => 'Caixa', 'url' => route('empresa.caixa.index')],
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

    <div class="mb-3">
        <a href="{{ route('empresa.caixa.fluxo-diario') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-graph-up-arrow me-1"></i>Fluxo do dia (entradas/saídas)</a>
    </div>

    @if (! $turnoAberto)
        <div class="vf-card p-4 mb-4" style="max-width: 28rem;">
            <h2 class="h5 fw-bold mb-3">Caixa fechado</h2>
            <p class="text-muted small mb-4">Informe o valor em gaveta ao iniciar o turno (pode ser zero). Só pode existir um caixa aberto por vez.</p>
            <form action="{{ route('empresa.caixa.abrir') }}" method="post">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="valor_abertura">Valor de abertura (R$)</label>
                    <input type="number" step="0.01" min="0" class="form-control @error('valor_abertura') is-invalid @enderror" id="valor_abertura" name="valor_abertura" value="{{ old('valor_abertura', '0') }}" required>
                    @error('valor_abertura')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label" for="obs_abertura">Observações</label>
                    <textarea class="form-control @error('obs_abertura') is-invalid @enderror" id="obs_abertura" name="obs_abertura" rows="2">{{ old('obs_abertura') }}</textarea>
                    @error('obs_abertura')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-success"><i class="bi bi-unlock me-1"></i>Abrir caixa</button>
            </form>
        </div>
    @else
        @php
            $movs = $turnoAberto->movimentos;
            $totSup = (float) $movs->whereIn('tipo', [\App\Models\CaixaMovimento::TIPO_SUPRIMENTO, \App\Models\CaixaMovimento::TIPO_ENTRADA_MANUAL])->sum('valor');
            $totVenda = (float) $movs->where('tipo', \App\Models\CaixaMovimento::TIPO_VENDA_AVULSA)->sum('valor');
            $totSang = (float) $movs->whereIn('tipo', [\App\Models\CaixaMovimento::TIPO_SANGRIA, \App\Models\CaixaMovimento::TIPO_SAIDA_MANUAL])->sum('valor');
            $saldo = $turnoAberto->saldoEsperado();
        @endphp
        <div class="alert alert-info small mb-3">
            Turno aberto desde {{ $turnoAberto->aberto_em->format('d/m/Y H:i') }}
            @if ($turnoAberto->usuario)
                — {{ $turnoAberto->usuario->name }}
            @endif
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">Abertura</div>
                        <div class="h5 mb-0">R$ {{ number_format((float) $turnoAberto->valor_abertura, 2, ',', '.') }}</div>
                    </div>
                    <i class="bi bi-unlock text-primary fs-4"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">Vendas (dinheiro)</div>
                        <div class="h5 mb-0 text-success">R$ {{ number_format($totVenda, 2, ',', '.') }}</div>
                    </div>
                    <i class="bi bi-cash text-success fs-4"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">Suprimentos / sangrias</div>
                        <div class="h5 mb-0 text-secondary">+{{ number_format($totSup, 2, ',', '.') }} / −{{ number_format($totSang, 2, ',', '.') }}</div>
                    </div>
                    <i class="bi bi-arrow-down-up text-secondary fs-4"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="vf-card vf-card-stat border-primary border-opacity-25">
                    <div>
                        <div class="small text-muted">Saldo esperado</div>
                        <div class="h5 mb-0 text-primary">R$ {{ number_format($saldo, 2, ',', '.') }}</div>
                    </div>
                    <i class="bi bi-calculator text-primary fs-4"></i>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="vf-card p-3 h-100">
                    <h2 class="h6 fw-bold mb-3">Novo lançamento</h2>
                    <form action="{{ route('empresa.caixa.movimento') }}" method="post">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="tipo-mov">Tipo</label>
                            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo-mov" name="tipo" required>
                                <option value="{{ \App\Models\CaixaMovimento::TIPO_ENTRADA_MANUAL }}" @selected(old('tipo') === \App\Models\CaixaMovimento::TIPO_ENTRADA_MANUAL)>Entrada manual</option>
                                <option value="{{ \App\Models\CaixaMovimento::TIPO_SAIDA_MANUAL }}" @selected(old('tipo') === \App\Models\CaixaMovimento::TIPO_SAIDA_MANUAL)>Saída manual</option>
                                <option value="{{ \App\Models\CaixaMovimento::TIPO_VENDA_AVULSA }}" @selected(old('tipo') === \App\Models\CaixaMovimento::TIPO_VENDA_AVULSA)>Venda (dinheiro)</option>
                                <option value="{{ \App\Models\CaixaMovimento::TIPO_SUPRIMENTO }}" @selected(old('tipo') === \App\Models\CaixaMovimento::TIPO_SUPRIMENTO)>Suprimento</option>
                                <option value="{{ \App\Models\CaixaMovimento::TIPO_SANGRIA }}" @selected(old('tipo') === \App\Models\CaixaMovimento::TIPO_SANGRIA)>Sangria</option>
                            </select>
                            @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="desc-mov">Descrição</label>
                            <input type="text" class="form-control @error('descricao') is-invalid @enderror" id="desc-mov" name="descricao" value="{{ old('descricao') }}" placeholder="Ex.: Pedido balcão, troco…" required>
                            @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="valor-mov">Valor (R$)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('valor') is-invalid @enderror" id="valor-mov" name="valor" value="{{ old('valor') }}" required>
                            @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Registrar</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="vf-card p-3 h-100">
                    <h2 class="h6 fw-bold mb-3">Fechar caixa</h2>
                    <p class="small text-muted">Conte o dinheiro físico e informe abaixo. O sistema compara com o saldo esperado (R$ {{ number_format($saldo, 2, ',', '.') }}).</p>
                    <form action="{{ route('empresa.caixa.fechar') }}" method="post" onsubmit="return confirm('Confirma o fechamento deste turno?');">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="valor_conf">Valor conferido em gaveta (R$)</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('valor_conferido_fechamento') is-invalid @enderror" id="valor_conf" name="valor_conferido_fechamento" value="{{ old('valor_conferido_fechamento') }}" required>
                            @error('valor_conferido_fechamento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="obs_fech">Observações do fechamento</label>
                            <textarea class="form-control @error('obs_fechamento') is-invalid @enderror" id="obs_fech" name="obs_fechamento" rows="2">{{ old('obs_fechamento') }}</textarea>
                            @error('obs_fechamento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-danger">Fechar caixa</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="vf-card p-0 overflow-hidden mb-3">
            <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h2 class="h6 fw-bold mb-0">Movimentações do turno</h2>
                <a href="{{ route('empresa.caixa.conferencia') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Imprimir conferência</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 vf-table">
                    <thead>
                        <tr><th>Hora</th><th>Tipo</th><th>Descrição</th><th class="text-end">Valor</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($turnoAberto->movimentos as $m)
                            <tr>
                                <td>{{ $m->created_at->format('H:i') }}</td>
                                <td>{{ \App\Models\CaixaMovimento::rotuloTipo($m->tipo) }}</td>
                                <td>{{ $m->descricao }}</td>
                                <td class="text-end fw-medium {{ $m->isEntrada() ? 'text-success' : 'text-danger' }}">
                                    {{ $m->isEntrada() ? '+' : '−' }} R$ {{ number_format((float) $m->valor, 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum lançamento ainda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($historico->isNotEmpty())
        <div class="vf-card p-0 overflow-hidden">
            <div class="p-3 border-bottom">
                <h2 class="h6 fw-bold mb-0">Últimos fechamentos</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 vf-table">
                    <thead>
                        <tr>
                            <th>Aberto</th>
                            <th>Fechado</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Conferido</th>
                            <th class="text-end">Diferença</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($historico as $h)
                            @php
                                $esp = $h->saldoEsperado();
                                $conf = (float) $h->valor_conferido_fechamento;
                                $dif = $h->diferencaFechamento() ?? 0.0;
                            @endphp
                            <tr>
                                <td class="small">{{ $h->aberto_em->format('d/m/Y H:i') }}</td>
                                <td class="small">{{ $h->fechado_em?->format('d/m/Y H:i') }}</td>
                                <td class="text-end small">R$ {{ number_format($esp, 2, ',', '.') }}</td>
                                <td class="text-end small">R$ {{ number_format($conf, 2, ',', '.') }}</td>
                                <td class="text-end small fw-semibold {{ abs($dif) < 0.01 ? 'text-success' : ($dif > 0 ? 'text-primary' : 'text-danger') }}">
                                    {{ $dif >= 0 ? '+' : '' }}{{ number_format($dif, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
