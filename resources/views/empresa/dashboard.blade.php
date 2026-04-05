@extends('layouts.empresa')

@section('title', 'Dashboard')

@section('content')
    @include('partials.components.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('empresa.dashboard')],
    ]])

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    @endif

    @if (! $empresa)
        <div class="alert alert-warning mb-4">
            Sua conta ainda não está vinculada a uma empresa no sistema. Peça ao administrador master para associar seu usuário a um cadastro de empresa.
        </div>
    @endif

    <div class="row g-3 mb-4">
        @if ($empresa)
            @php
                $planoNome = $empresa->plano?->nome ?? '—';
                $statusLabel = match ($empresa->status) {
                    'ativa' => 'Ativa',
                    'trial' => 'Trial',
                    default => 'Suspensa',
                };
                $statusTone = match ($empresa->status) {
                    'ativa' => 'success',
                    'trial' => 'warning',
                    default => 'secondary',
                };
                $desde = $empresa->cliente_desde ? $empresa->cliente_desde->format('d/m/Y') : '—';
            @endphp
            <div class="col-6 col-xl-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">Plano atual</div>
                        <div class="h4 mb-0 fw-bold text-truncate" title="{{ $planoNome }}">{{ $planoNome }}</div>
                    </div>
                    <div class="icon-wrap bg-primary-subtle text-primary"><i class="bi bi-tag"></i></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">Situação da conta</div>
                        <div class="h4 mb-0 fw-bold">{{ $statusLabel }}</div>
                        <div class="small text-muted mt-1">Desde {{ $desde }}</div>
                    </div>
                    <div class="icon-wrap bg-{{ $statusTone }}-subtle text-{{ $statusTone }}"><i class="bi bi-shield-check"></i></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">Assinatura</div>
                        @if ($assinatura)
                            <div class="h4 mb-0 fw-bold">R$ {{ number_format((float) $assinatura->valor_mensal, 2, ',', '.') }}</div>
                            <div class="small text-muted mt-1">
                                Próx.: {{ $assinatura->proxima_cobranca->format('d/m/Y') }}
                                @if ($assinatura->status === 'paga')
                                    <span class="vf-badge bg-success-subtle text-success ms-1">Paga</span>
                                @else
                                    <span class="vf-badge bg-warning-subtle text-warning ms-1">Pendente</span>
                                @endif
                            </div>
                        @else
                            <div class="h6 mb-0 fw-semibold text-muted">Sem registro</div>
                            <div class="small text-muted mt-1">Cadastre no painel master</div>
                        @endif
                    </div>
                    <div class="icon-wrap bg-success-subtle text-success"><i class="bi bi-credit-card"></i></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="vf-card vf-card-stat">
                    <div>
                        <div class="small text-muted">Chamados em aberto</div>
                        <div class="h4 mb-0 fw-bold">{{ $ticketsAbertos }}</div>
                        <div class="small text-muted mt-1"><a href="{{ route('empresa.chamados.index') }}" class="text-decoration-none">Ver suporte</a></div>
                    </div>
                    <div class="icon-wrap bg-{{ $ticketsAbertos > 0 ? 'warning' : 'secondary' }}-subtle text-{{ $ticketsAbertos > 0 ? 'warning' : 'secondary' }}"><i class="bi bi-headset"></i></div>
                </div>
            </div>
        @else
            <div class="col-12">
                <div class="vf-card vf-card-stat p-4 text-muted small">Faça login com um usuário vinculado a uma empresa para ver o resumo da conta.</div>
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="vf-card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold mb-0">Chamados ao suporte (últimos 7 dias)</h2>
                    @if ($empresa)
                        <span class="vf-badge bg-primary-subtle text-primary">Dados reais</span>
                    @endif
                </div>
                @if ($empresa && count($chartHeights))
                    <div class="vf-chart-fake">@foreach ($chartHeights as $h)<div class="bar" style="height:{{ max(4, $h) }}%"></div>@endforeach</div>
                    <p class="small text-muted mb-0 mt-2">Novos tickets por dia (escala relativa ao maior dia).</p>
                @elseif ($empresa)
                    <p class="text-muted small mb-0">Nenhum chamado registrado neste período.</p>
                @else
                    <p class="text-muted small mb-0">—</p>
                @endif
            </div>
            <div class="vf-card p-0 overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h2 class="h6 fw-bold mb-0">Chamados recentes</h2>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('empresa.chamados.index') }}" class="small">Ver todos</a>
                        <a href="{{ route('empresa.pedidos.index') }}" class="small text-muted">Pedidos (demo)</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 vf-table">
                        <thead><tr><th>#</th><th>Assunto</th><th>Status</th><th class="text-end">Atualizado</th></tr></thead>
                        <tbody>
                            @if ($empresa && $ticketsRecentes->isNotEmpty())
                                @foreach ($ticketsRecentes as $t)
                                    @php
                                        $sTone = match ($t->status) {
                                            'aberto' => 'info',
                                            'aguardando' => 'secondary',
                                            'em_andamento' => 'primary',
                                            'resolvido' => 'success',
                                            default => 'dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('empresa.chamados.show', ['suporteTicket' => $t]) }}" class="fw-semibold text-decoration-none">#{{ $t->id }}</a></td>
                                        <td>{{ \Illuminate\Support\Str::limit($t->assunto, 50) }}</td>
                                        <td><span class="vf-badge bg-{{ $sTone }}-subtle text-{{ $sTone }}">{{ \App\Models\SuporteTicket::statusRotulos()[$t->status] }}</span></td>
                                        <td class="text-end small">{{ $t->updated_at->format('d/m H:i') }}</td>
                                    </tr>
                                @endforeach
                            @elseif ($empresa)
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4 small">Nenhum chamado ainda. <a href="{{ route('empresa.chamados.create') }}">Abrir chamado</a> em Suporte.</td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4 small">Vincule uma empresa para ver chamados.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @if ($empresa)
                <div class="vf-card p-3 mb-3">
                    <h2 class="h6 fw-bold mb-2">{{ $empresa->nome }}</h2>
                    <p class="small text-muted mb-2">{{ $empresa->email_contato ?? 'Sem e-mail de contato cadastrado.' }}</p>
                    @if ($empresa->modulos_resumo)
                        <p class="small mb-0"><span class="text-muted">Módulos:</span> {{ $empresa->modulos_resumo }}</p>
                    @endif
                </div>
            @endif
            <div class="vf-card p-3 mb-3">
                <h2 class="h6 fw-bold mb-3">Atalhos</h2>
                <div class="d-grid gap-2">
                    <a href="{{ route('empresa.chamados.index') }}" class="btn btn-outline-secondary btn-sm text-start"><i class="bi bi-headset me-2"></i>Meus chamados</a>
                    <a href="{{ route('empresa.produtos.create') }}" class="btn btn-outline-primary btn-sm text-start"><i class="bi bi-plus-lg me-2"></i>Novo produto</a>
                    <a href="{{ route('empresa.venda-externa.remessas.index') }}" class="btn btn-outline-primary btn-sm text-start"><i class="bi bi-boxes me-2"></i>Nova remessa</a>
                    <a href="{{ route('empresa.caixa.index') }}" class="btn btn-outline-success btn-sm text-start"><i class="bi bi-cash-stack me-2"></i>Abrir caixa</a>
                </div>
            </div>
            <div class="vf-card p-3">
                <h2 class="h6 fw-bold mb-2">Alertas</h2>
                @if ($empresa)
                    @if ($assinatura && $assinatura->status === 'pendente')
                        <div class="alert alert-warning small mb-2 py-2">Há cobrança pendente. Próxima data: {{ $assinatura->proxima_cobranca->format('d/m/Y') }}.</div>
                    @endif
                    @if ($ticketsAbertos > 0)
                        <div class="alert alert-info small mb-0 py-2">{{ $ticketsAbertos }} chamado(s) de suporte aguardando acompanhamento.</div>
                    @else
                        <div class="alert alert-light border small mb-0 py-2">Nenhum alerta. Pedidos e estoque serão integrados nas próximas etapas.</div>
                    @endif
                @else
                    <div class="alert alert-warning small mb-0 py-2">Associe seu usuário a uma empresa para personalizar este painel.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
