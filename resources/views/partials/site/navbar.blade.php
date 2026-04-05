<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="{{ route('site.home') }}">
            <i class="bi bi-bag-heart-fill me-1"></i>{{ config('app.name') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vfSiteNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="vfSiteNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="{{ route('site.planos') }}">Planos</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('site.sobre') }}">Sobre</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('site.contato') }}">Contato</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('publico.loja', ['slug' => 'demo']) }}">Ver loja demo</a></li>
                <li class="nav-item ms-lg-2">
                    @auth
                        <a class="btn btn-outline-primary btn-sm" href="{{ Auth::user()->isAdmin() ? route('admin.dashboard') : route('empresa.dashboard') }}">Painel</a>
                    @else
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('login') }}">Entrar</a>
                    @endauth
                </li>
                <li class="nav-item">
                    <a class="btn btn-primary btn-sm" href="{{ route('auth.cadastro-empresa') }}">Começar grátis</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
