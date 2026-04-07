@php
    $isVe = request()->routeIs('empresa.venda-externa.*');
@endphp
<aside class="vf-sidebar" data-vf-sidebar>
    <div class="vf-sidebar-brand text-white">
        <i class="bi bi-shop-window me-1"></i>Boa<span class="text-info">Vendas</span>
        <div class="small text-white-50 fw-normal mt-1">Painel da empresa</div>
    </div>
    <nav class="nav flex-column px-2 py-3">
        <a class="nav-link {{ request()->routeIs('empresa.dashboard') ? 'active' : '' }}" href="{{ route('empresa.dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.pedidos.*') ? 'active' : '' }}" href="{{ route('empresa.pedidos.index') }}">
            <i class="bi bi-receipt"></i> Pedidos
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.produtos.*') ? 'active' : '' }}" href="{{ route('empresa.produtos.index') }}">
            <i class="bi bi-box-seam"></i> Produtos
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.categorias.*') ? 'active' : '' }}" href="{{ route('empresa.categorias.index') }}">
            <i class="bi bi-grid"></i> Categorias
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.adicionais.*') ? 'active' : '' }}" href="{{ route('empresa.adicionais.index') }}">
            <i class="bi bi-plus-square-dotted"></i> Adicionais
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.clientes.*') ? 'active' : '' }}" href="{{ route('empresa.clientes.index') }}">
            <i class="bi bi-people"></i> Clientes
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.fidelidade.*') ? 'active' : '' }}" href="{{ route('empresa.fidelidade.programa') }}">
            <i class="bi bi-award"></i> Fidelidade
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.entregas.*') ? 'active' : '' }}" href="{{ route('empresa.entregas.index') }}">
            <i class="bi bi-truck"></i> Entregas
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.financeiro.*') ? 'active' : '' }}" href="{{ route('empresa.financeiro.index') }}">
            <i class="bi bi-currency-dollar"></i> Financeiro
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.caixa.*') ? 'active' : '' }}" href="{{ route('empresa.caixa.index') }}">
            <i class="bi bi-cash-stack"></i> Caixa
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.relatorios.*') && !$isVe ? 'active' : '' }}" href="{{ route('empresa.relatorios.index') }}">
            <i class="bi bi-graph-up-arrow"></i> Relatórios
        </a>

        <div class="px-2 pt-2 pb-1 small text-white-50 text-uppercase">Venda externa</div>
        <div class="submenu">
            <a class="nav-link {{ request()->routeIs('empresa.venda-externa.dashboard') ? 'active' : '' }}" href="{{ route('empresa.venda-externa.dashboard') }}">
                <i class="bi bi-pin-map"></i> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('empresa.venda-externa.pontos', 'empresa.venda-externa.pontos.*') ? 'active' : '' }}" href="{{ route('empresa.venda-externa.pontos') }}">
                <i class="bi bi-geo-alt"></i> Pontos
            </a>
            <a class="nav-link {{ request()->routeIs('empresa.venda-externa.remessas.*') ? 'active' : '' }}" href="{{ route('empresa.venda-externa.remessas.index') }}">
                <i class="bi bi-boxes"></i> Remessas
            </a>
            <a class="nav-link {{ request()->routeIs('empresa.venda-externa.acertos', 'empresa.venda-externa.acertos.*') ? 'active' : '' }}" href="{{ route('empresa.venda-externa.acertos') }}">
                <i class="bi bi-check2-circle"></i> Acertos
            </a>
            <a class="nav-link {{ request()->routeIs('empresa.venda-externa.fiados', 'empresa.venda-externa.fiados.*') ? 'active' : '' }}" href="{{ route('empresa.venda-externa.fiados') }}">
                <i class="bi bi-journal-text"></i> Fiados
            </a>
            <a class="nav-link {{ request()->routeIs('empresa.venda-externa.relatorios', 'empresa.venda-externa.relatorios.*') ? 'active' : '' }}" href="{{ route('empresa.venda-externa.relatorios') }}">
                <i class="bi bi-pie-chart"></i> Relatórios VE
            </a>
        </div>

        <hr class="border-secondary opacity-25 mx-2">
        <a class="nav-link {{ request()->routeIs('empresa.chamados.*') ? 'active' : '' }}" href="{{ route('empresa.chamados.index') }}">
            <i class="bi bi-headset"></i> Suporte
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.configuracoes.*') ? 'active' : '' }}" href="{{ route('empresa.configuracoes.index') }}">
            <i class="bi bi-gear"></i> Configurações
        </a>
        <a class="nav-link {{ request()->routeIs('empresa.usuarios.*') ? 'active' : '' }}" href="{{ route('empresa.usuarios.index') }}">
            <i class="bi bi-person-badge"></i> Usuários
        </a>
    </nav>
</aside>
