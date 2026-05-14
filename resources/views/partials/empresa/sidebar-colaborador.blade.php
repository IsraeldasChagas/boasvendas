@php
    $r = Auth::user()?->role ?? '';
    $ehAtendente = $r === \App\Models\User::ROLE_ATENDENTE;
    $ehCaixa = $r === \App\Models\User::ROLE_ATENDENTE_CAIXA;
@endphp
@if (\Illuminate\Support\Facades\Schema::hasTable('mesas'))
    @php $mesasMenuAtivo = request()->routeIs('empresa.mesas.*', 'empresa.comandas.*'); @endphp
    <button type="button" class="nav-link vf-submenu-toggle {{ $mesasMenuAtivo ? 'active' : '' }}" data-vf-submenu-toggle aria-expanded="{{ $mesasMenuAtivo ? 'true' : 'false' }}">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-grid-3x3-gap"></i> Vendas por mesa
        </span>
        <i class="bi bi-chevron-right vf-submenu-chevron"></i>
    </button>
    <div class="submenu vf-submenu-content {{ $mesasMenuAtivo ? '' : 'd-none' }}">
        <a class="nav-link {{ request()->routeIs('empresa.mesas.index') ? 'active' : '' }}" href="{{ route('empresa.mesas.index') }}">
            <i class="bi bi-map"></i> Mapa de mesas
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.mesas.comandas-abertas') ? 'active' : '' }}" href="{{ route('empresa.mesas.comandas-abertas') }}">
            <i class="bi bi-journal-bookmark"></i> Comandas abertas
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.mesas.cozinha') ? 'active' : '' }}" href="{{ route('empresa.mesas.cozinha') }}">
            <i class="bi bi-fire"></i> Painel da cozinha
        </a>
        @if (! $ehAtendente)
            <a class="nav-link {{ request()->routeIs('empresa.mesas.fechamento.*') ? 'active' : '' }}" href="{{ route('empresa.mesas.fechamento.index') }}">
                <i class="bi bi-cash-stack"></i> Fechamento de mesa
            </a>
            <a class="nav-link {{ request()->routeIs('empresa.mesas.relatorios') ? 'active' : '' }}" href="{{ route('empresa.mesas.relatorios') }}">
                <i class="bi bi-graph-up"></i> Relatórios de mesas
            </a>
        @endif
    </div>
@endif

@if ($ehCaixa)
    @php
        $badgePedPend = $empresa ? \App\Models\Pedido::query()->where('empresa_id', $empresa->id)->where('status', \App\Models\Pedido::STATUS_PENDENTE_LOJA)->count() : 0;
    @endphp
    <a class="nav-link {{ request()->routeIs('empresa.pedidos.*') ? 'active' : '' }}" href="{{ route('empresa.pedidos.index') }}">
        <i class="bi bi-receipt"></i> Pedidos
        @if ($badgePedPend > 0)
            <span class="badge bg-danger rounded-pill ms-1">{{ $badgePedPend }}</span>
        @endif
    </a>
    <a class="nav-link {{ request()->routeIs('empresa.pdv.*') ? 'active' : '' }}" href="{{ route('empresa.pdv.index') }}">
        <i class="bi bi-cash-coin"></i> PDV — Novo pedido
    </a>
    <a class="nav-link {{ request()->routeIs('empresa.frete-calculadora.*') ? 'active' : '' }}" href="{{ route('empresa.frete-calculadora.index') }}">
        <i class="bi bi-calculator"></i> Calcular frete
    </a>
    @if (\Illuminate\Support\Facades\Schema::hasTable('empresa_entregadores'))
        <a class="nav-link {{ request()->routeIs('empresa.entregadores.*') ? 'active' : '' }}" href="{{ route('empresa.entregadores.index') }}">
            <i class="bi bi-person-badge"></i> Meus entregadores
        </a>
    @endif
    @php
        $caixaItensColab = [
            'caixa_visao' => ['active' => request()->routeIs('empresa.caixa.index'), 'url' => route('empresa.caixa.index'), 'icon' => 'bi-cash-stack', 'label' => 'Visão geral'],
            'caixa_fluxo_diario' => ['active' => request()->routeIs('empresa.caixa.fluxo-diario'), 'url' => route('empresa.caixa.fluxo-diario'), 'icon' => 'bi-graph-up-arrow', 'label' => 'Fluxo do dia'],
            'caixa_operacoes' => ['active' => request()->routeIs('empresa.caixa.abrir', 'empresa.caixa.movimento', 'empresa.caixa.fechar'), 'url' => route('empresa.caixa.index'), 'icon' => 'bi-lightning-charge', 'label' => 'Operações'],
            'caixa_conferencia' => ['active' => request()->routeIs('empresa.caixa.conferencia'), 'url' => route('empresa.caixa.conferencia'), 'icon' => 'bi-clipboard-check', 'label' => 'Conferência'],
        ];
        $caixaAtivoColab = false;
        foreach ($caixaItensColab as $it) {
            if (($it['active'] ?? false) === true) {
                $caixaAtivoColab = true;
            }
        }
    @endphp
    <button type="button" class="nav-link vf-submenu-toggle {{ $caixaAtivoColab ? 'active' : '' }}" data-vf-submenu-toggle aria-expanded="{{ $caixaAtivoColab ? 'true' : 'false' }}">
        <span class="d-flex align-items-center gap-2">
            <i class="bi bi-cash-stack"></i> Caixa
        </span>
        <i class="bi bi-chevron-right vf-submenu-chevron"></i>
    </button>
    <div class="submenu vf-submenu-content {{ $caixaAtivoColab ? '' : 'd-none' }}">
        @foreach ($caixaItensColab as $it)
            <a class="nav-link {{ $it['active'] ? 'active' : '' }}" href="{{ $it['url'] }}">
                <i class="bi {{ $it['icon'] }}"></i> {{ $it['label'] }}
            </a>
        @endforeach
    </div>
@endif
