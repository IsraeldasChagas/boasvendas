<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="{{ asset('pwa/manifest.json') }}">
    <title>@yield('title', 'Conta') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="{{ asset('assets/css/boasvandas.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <div class="vf-auth-shell">
        <div class="vf-auth-card">
            <div class="text-center mb-4">
                <a href="{{ route('site.home') }}" class="text-decoration-none fw-bold fs-4 text-primary">
                    <i class="bi bi-bag-heart-fill"></i> {{ config('app.name') }}
                </a>
                <p class="small text-muted mb-0 mt-2">Área de conta (demonstração visual)</p>
            </div>
            <div class="vf-card p-4 shadow-sm">
                @yield('content')
            </div>
            <p class="text-center small text-muted mt-3 mb-0">
                <a href="{{ route('site.home') }}">Voltar ao site</a>
            </p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('assets/js/boasvandas.js') }}"></script>
    @stack('scripts')
</body>
</html>
