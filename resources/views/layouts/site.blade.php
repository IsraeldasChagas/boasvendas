<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="{{ asset('pwa/manifest.json') }}">
    <title>@yield('title', 'Início') — {{ config('app.name') }} · SaaS para vendas e delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="{{ asset('assets/css/vendaffacil.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="vf-body d-flex flex-column min-vh-100">
    @include('partials.site.navbar')
    <main class="flex-grow-1">
        @if (session('warning'))
            <div class="container pt-3"><div class="alert alert-warning alert-dismissible fade show mb-0" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div></div>
        @endif
        @yield('content')
    </main>
    @include('partials.site.footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('assets/js/vendaffacil.js') }}"></script>
    @stack('scripts')
</body>
</html>
