@extends('layouts.empresa')

@section('title', 'Venda externa — Dashboard')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Venda externa', 'url' => route('empresa.venda-externa.dashboard')],
        ['label' => 'Dashboard', 'url' => route('empresa.venda-externa.dashboard')],
    ]])

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Pontos ativos</div>
                    <div class="h4 mb-0 fw-bold">{{ $pontosAtivos }}</div>
                </div>
                <div class="icon-wrap bg-primary-subtle text-primary"><i class="bi bi-pin-map"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Entregas em campo</div>
                    <div class="h4 mb-0 fw-bold">{{ $remessasEmCampo }}</div>
                </div>
                <div class="icon-wrap bg-warning-subtle text-warning"><i class="bi bi-box-seam"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Fiado em aberto</div>
                    <div class="h4 mb-0 fw-bold text-danger">R$ {{ number_format($fiadoAberto, 2, ',', '.') }}</div>
                </div>
                <div class="icon-wrap bg-danger-subtle text-danger"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="vf-card vf-card-stat">
                <div>
                    <div class="small text-muted">Acertos (7 dias)</div>
                    <div class="h4 mb-0 fw-bold">{{ $acertosPendentes }}</div>
                    <div class="small text-muted mt-1">Pontos com visita prevista</div>
                </div>
                <div class="icon-wrap bg-success-subtle text-success"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="vf-card p-3">
                <h2 class="h6 fw-bold mb-1">Venda externa por semana</h2>
                <p class="small text-muted mb-3">Soma dos registros de venda nos últimos 12 semanas (por data de venda).</p>
                @php $somaChart = array_sum($chartValores); @endphp
                @if ($somaChart <= 0)
                    <p class="text-muted small mb-0 py-4 text-center">Sem registros de venda. Cadastre pontos e lance vendas quando o módulo de lançamentos estiver disponível.</p>
                @else
                    <div class="vf-chart-fake" style="height: 200px;">
                        @foreach ($chartLabels as $i => $label)
                            @php
                                $v = $chartValores[$i] ?? 0;
                                $h = $chartMax > 0 && $v > 0 ? max(12, (int) round($v / $chartMax * 100)) : ($v > 0 ? 12 : 4);
                            @endphp
                            <div class="bar" style="height: {{ $h }}%; opacity: .9; background: linear-gradient(180deg,#16a34a,#2563eb);" title="Sem. {{ $label }} — R$ {{ number_format($v, 2, ',', '.') }}"></div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between gap-1 small text-muted mt-1 px-1">
                        @foreach ($chartLabels as $label)
                            <span class="text-center flex-fill" style="font-size: 0.65rem;">{{ $label }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="vf-card p-3 mb-3">
                <h2 class="h6 fw-bold mb-2">Próximos acertos</h2>
                @if ($proximosAcertos->isEmpty())
                    <p class="small text-muted mb-0">Nenhum ponto com data de acerto definida.</p>
                @else
                    <ul class="list-group list-group-flush small">
                        @foreach ($proximosAcertos as $p)
                            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span>
                                    <strong>{{ $p->nome }}</strong>
                                    @if ($p->regiao)
                                        <span class="text-muted">· {{ $p->regiao }}</span>
                                    @endif
                                    <span class="d-block text-muted">{{ $p->proximo_acerto_em->format('d/m/Y H:i') }}</span>
                                </span>
                                <span class="vf-badge {{ $p->proximo_acerto_em->isPast() ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' }}">
                                    {{ $p->proximo_acerto_em->isPast() ? 'Atrasado' : $p->proximo_acerto_em->translatedFormat('D') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="d-grid gap-2">
                <a href="{{ route('empresa.venda-externa.pontos') }}" class="btn btn-outline-secondary btn-sm">Ver pontos</a>
                <a href="{{ route('empresa.venda-externa.remessas.index') }}" class="btn btn-outline-primary btn-sm">Ver entregas</a>
                <a href="{{ route('empresa.venda-externa.fiados') }}" class="btn btn-outline-danger btn-sm">Abrir fiados</a>
            </div>
        </div>
    </div>
@endsection
