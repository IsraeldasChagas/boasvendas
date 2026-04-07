@php
    $isVe = request()->routeIs('empresa.venda-externa.*');
    $empresa = Auth::user()?->empresa;
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
    @php $menu = $empresa?->telasMenuEmpresaLiberadas() ?? []; @endphp
    @php $tem = fn (string $k) => $menu === [] ? true : in_array($k, $menu, true); @endphp
    @if ($tem('pedidos'))
        <a class="nav-link {{ request()->routeIs('empresa.pedidos.*') ? 'active' : '' }}" href="{{ route('empresa.pedidos.index') }}">
            <i class="bi bi-receipt"></i> Pedidos
        </a>
    @endif
    @if ($tem('produtos'))
        <a class="nav-link {{ request()->routeIs('empresa.produtos.*') ? 'active' : '' }}" href="{{ route('empresa.produtos.index') }}">
            <i class="bi bi-box-seam"></i> Produtos
        </a>
    @endif
    @if ($tem('categorias'))
        <a class="nav-link {{ request()->routeIs('empresa.categorias.*') ? 'active' : '' }}" href="{{ route('empresa.categorias.index') }}">
            <i class="bi bi-grid"></i> Categorias
        </a>
    @endif
    @if ($tem('adicionais'))
        <a class="nav-link {{ request()->routeIs('empresa.adicionais.*') ? 'active' : '' }}" href="{{ route('empresa.adicionais.index') }}">
            <i class="bi bi-plus-square-dotted"></i> Adicionais
        </a>
    @endif
    @if ($tem('clientes'))
        <a class="nav-link {{ request()->routeIs('empresa.clientes.*') ? 'active' : '' }}" href="{{ route('empresa.clientes.index') }}">
            <i class="bi bi-people"></i> Clientes
        </a>
    @endif
    @php
        $fidItens = [
            'fidelidade_programa' => ['active' => request()->routeIs('empresa.fidelidade.programa', 'empresa.fidelidade.programa.*'), 'url' => route('empresa.fidelidade.programa'), 'icon' => 'bi-award', 'label' => 'Programa'],
            'fidelidade_cartoes' => ['active' => request()->routeIs('empresa.fidelidade.cartoes', 'empresa.fidelidade.cartoes.*'), 'url' => route('empresa.fidelidade.cartoes'), 'icon' => 'bi-ticket-perforated', 'label' => 'Cartões'],
        ];
        $temAlgumFid = false;
        $fidAtivo = false;
        foreach (array_keys($fidItens) as $k) {
            if ($tem($k)) {
                $temAlgumFid = true;
                if (($fidItens[$k]['active'] ?? false) === true) {
                    $fidAtivo = true;
                }
            }
        }
    @endphp
    @if ($temAlgumFid)
        <button type="button" class="nav-link vf-submenu-toggle {{ $fidAtivo ? 'active' : '' }}" data-vf-submenu-toggle aria-expanded="{{ $fidAtivo ? 'true' : 'false' }}">
            <span class="d-flex align-items-center gap-2">
                <i class="bi bi-award"></i> Fidelidade
            </span>
            <i class="bi bi-chevron-right vf-submenu-chevron"></i>
        </button>
        <div class="submenu vf-submenu-content {{ $fidAtivo ? '' : 'd-none' }}">
            @foreach ($fidItens as $k => $it)
                @if ($tem($k))
                    <a class="nav-link {{ $it['active'] ? 'active' : '' }}" href="{{ $it['url'] }}">
                        <i class="bi {{ $it['icon'] }}"></i> {{ $it['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif
    @if ($tem('entregas'))
        <a class="nav-link {{ request()->routeIs('empresa.entregas.*') ? 'active' : '' }}" href="{{ route('empresa.entregas.index') }}">
            <i class="bi bi-truck"></i> Entregas
        </a>
    @endif
    @php
        $finItens = [
            'financeiro_visao' => ['active' => request()->routeIs('empresa.financeiro.index'), 'url' => route('empresa.financeiro.index'), 'icon' => 'bi-currency-dollar', 'label' => 'Visão geral'],
            'financeiro_receber' => ['active' => request()->routeIs('empresa.financeiro.contas-receber*'), 'url' => route('empresa.financeiro.contas-receber'), 'icon' => 'bi-arrow-down-circle', 'label' => 'Contas a receber'],
            'financeiro_pagar' => ['active' => request()->routeIs('empresa.financeiro.contas-pagar*'), 'url' => route('empresa.financeiro.contas-pagar'), 'icon' => 'bi-arrow-up-circle', 'label' => 'Contas a pagar'],
        ];
        $temAlgumFin = false;
        $finAtivo = false;
        foreach (array_keys($finItens) as $k) {
            if ($tem($k)) {
                $temAlgumFin = true;
                if (($finItens[$k]['active'] ?? false) === true) {
                    $finAtivo = true;
                }
            }
        }
        $caixaItens = [
            'caixa_visao' => ['active' => request()->routeIs('empresa.caixa.index'), 'url' => route('empresa.caixa.index'), 'icon' => 'bi-cash-stack', 'label' => 'Visão geral'],
            'caixa_operacoes' => ['active' => request()->routeIs('empresa.caixa.abrir', 'empresa.caixa.movimento', 'empresa.caixa.fechar'), 'url' => route('empresa.caixa.index'), 'icon' => 'bi-lightning-charge', 'label' => 'Operações'],
            'caixa_conferencia' => ['active' => request()->routeIs('empresa.caixa.conferencia'), 'url' => route('empresa.caixa.conferencia'), 'icon' => 'bi-clipboard-check', 'label' => 'Conferência'],
        ];
        $temAlgumCaixa = false;
        $caixaAtivo = false;
        foreach (array_keys($caixaItens) as $k) {
            if ($tem($k)) {
                $temAlgumCaixa = true;
                if (($caixaItens[$k]['active'] ?? false) === true) {
                    $caixaAtivo = true;
                }
            }
        }
    @endphp
    @if ($temAlgumFin)
        <button type="button" class="nav-link vf-submenu-toggle {{ $finAtivo ? 'active' : '' }}" data-vf-submenu-toggle aria-expanded="{{ $finAtivo ? 'true' : 'false' }}">
            <span class="d-flex align-items-center gap-2">
                <i class="bi bi-currency-dollar"></i> Financeiro
            </span>
            <i class="bi bi-chevron-right vf-submenu-chevron"></i>
        </button>
        <div class="submenu vf-submenu-content {{ $finAtivo ? '' : 'd-none' }}">
            @foreach ($finItens as $k => $it)
                @if ($tem($k))
                    <a class="nav-link {{ $it['active'] ? 'active' : '' }}" href="{{ $it['url'] }}">
                        <i class="bi {{ $it['icon'] }}"></i> {{ $it['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif
    @if ($temAlgumCaixa)
        <button type="button" class="nav-link vf-submenu-toggle {{ $caixaAtivo ? 'active' : '' }}" data-vf-submenu-toggle aria-expanded="{{ $caixaAtivo ? 'true' : 'false' }}">
            <span class="d-flex align-items-center gap-2">
                <i class="bi bi-cash-stack"></i> Caixa
            </span>
            <i class="bi bi-chevron-right vf-submenu-chevron"></i>
        </button>
        <div class="submenu vf-submenu-content {{ $caixaAtivo ? '' : 'd-none' }}">
            @foreach ($caixaItens as $k => $it)
                @if ($tem($k))
                    <a class="nav-link {{ $it['active'] ? 'active' : '' }}" href="{{ $it['url'] }}">
                        <i class="bi {{ $it['icon'] }}"></i> {{ $it['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif
    @if ($tem('relatorios'))
        <a class="nav-link {{ request()->routeIs('empresa.relatorios.*') && !$isVe ? 'active' : '' }}" href="{{ route('empresa.relatorios.index') }}">
            <i class="bi bi-graph-up-arrow"></i> Relatórios
        </a>
    @endif

    @php
        $veItens = [
            've_dashboard' => ['active' => request()->routeIs('empresa.venda-externa.dashboard'), 'url' => route('empresa.venda-externa.dashboard'), 'icon' => 'bi-pin-map', 'label' => 'Dashboard'],
            've_pontos' => ['active' => request()->routeIs('empresa.venda-externa.pontos', 'empresa.venda-externa.pontos.*'), 'url' => route('empresa.venda-externa.pontos'), 'icon' => 'bi-geo-alt', 'label' => 'Pontos'],
            've_remessas' => ['active' => request()->routeIs('empresa.venda-externa.remessas.*'), 'url' => route('empresa.venda-externa.remessas.index'), 'icon' => 'bi-boxes', 'label' => 'Remessas'],
            've_acertos' => ['active' => request()->routeIs('empresa.venda-externa.acertos', 'empresa.venda-externa.acertos.*'), 'url' => route('empresa.venda-externa.acertos'), 'icon' => 'bi-check2-circle', 'label' => 'Acertos'],
            've_fiados' => ['active' => request()->routeIs('empresa.venda-externa.fiados', 'empresa.venda-externa.fiados.*'), 'url' => route('empresa.venda-externa.fiados'), 'icon' => 'bi-journal-text', 'label' => 'Fiados'],
            've_relatorios' => ['active' => request()->routeIs('empresa.venda-externa.relatorios', 'empresa.venda-externa.relatorios.*'), 'url' => route('empresa.venda-externa.relatorios'), 'icon' => 'bi-pie-chart', 'label' => 'Relatórios VE'],
        ];
        $temAlgumVe = false;
        $veAtivo = false;
        foreach (array_keys($veItens) as $k) {
            if ($tem($k)) {
                $temAlgumVe = true;
                if (($veItens[$k]['active'] ?? false) === true) {
                    $veAtivo = true;
                }
                break;
            }
        }
    @endphp
    @if ($temAlgumVe)
        <button type="button" class="nav-link vf-submenu-toggle {{ $veAtivo ? 'active' : '' }}" data-vf-submenu-toggle aria-expanded="{{ $veAtivo ? 'true' : 'false' }}">
            <span class="d-flex align-items-center gap-2">
                <i class="bi bi-pin-map"></i> Venda externa
            </span>
            <i class="bi bi-chevron-right vf-submenu-chevron"></i>
        </button>
        <div class="submenu vf-submenu-content {{ $veAtivo ? '' : 'd-none' }}">
            @foreach ($veItens as $k => $it)
                @if ($tem($k))
                    <a class="nav-link {{ $it['active'] ? 'active' : '' }}" href="{{ $it['url'] }}">
                        <i class="bi {{ $it['icon'] }}"></i> {{ $it['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif

        <hr class="border-secondary opacity-25 mx-2">
    @if ($tem('suporte'))
        <a class="nav-link {{ request()->routeIs('empresa.chamados.*') ? 'active' : '' }}" href="{{ route('empresa.chamados.index') }}">
            <i class="bi bi-headset"></i> Suporte
        </a>
    @endif
    @if ($tem('configuracoes'))
        <a class="nav-link {{ request()->routeIs('empresa.configuracoes.*') ? 'active' : '' }}" href="{{ route('empresa.configuracoes.index') }}">
            <i class="bi bi-gear"></i> Configurações
        </a>
    @endif
    @if ($tem('usuarios'))
        <a class="nav-link {{ request()->routeIs('empresa.usuarios.*') ? 'active' : '' }}" href="{{ route('empresa.usuarios.index') }}">
            <i class="bi bi-person-badge"></i> Usuários
        </a>
    @endif
    </nav>
</aside>
