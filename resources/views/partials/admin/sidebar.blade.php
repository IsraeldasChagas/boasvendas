<aside class="vf-sidebar" data-vf-sidebar>
    <div class="vf-sidebar-brand text-white">
        <i class="bi bi-shield-lock me-1"></i>{{ config('app.name') }}
        <div class="small text-white-50 fw-normal mt-1">Painel master · SaaS</div>
    </div>
    <nav class="nav flex-column px-2 py-3">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}" href="{{ route('admin.usuarios.index') }}">
            <i class="bi bi-people"></i> Usuários
        </a>
        <a class="nav-link {{ request()->routeIs('admin.empresas.*') ? 'active' : '' }}" href="{{ route('admin.empresas.index') }}">
            <i class="bi bi-buildings"></i> Empresas
        </a>
        <a class="nav-link {{ request()->routeIs('admin.planos.*') ? 'active' : '' }}" href="{{ route('admin.planos.index') }}">
            <i class="bi bi-tags"></i> Planos
        </a>
        <a class="nav-link {{ request()->routeIs('admin.modulos.*') ? 'active' : '' }}" href="{{ route('admin.modulos.index') }}">
            <i class="bi bi-puzzle"></i> Módulos
        </a>
        <a class="nav-link {{ request()->routeIs('admin.assinaturas.*') ? 'active' : '' }}" href="{{ route('admin.assinaturas.index') }}">
            <i class="bi bi-credit-card-2-front"></i> Assinaturas
        </a>
        <a class="nav-link {{ request()->routeIs('admin.suporte.*') ? 'active' : '' }}" href="{{ route('admin.suporte.index') }}">
            <i class="bi bi-headset"></i> Suporte
        </a>
        <hr class="border-secondary opacity-25 mx-2">
        <a class="nav-link" href="{{ route('site.home') }}"><i class="bi bi-house"></i> Site público</a>
    </nav>
</aside>
