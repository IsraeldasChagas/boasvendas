<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sem acesso — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="{{ asset('assets/css/vendaffacil.css') }}" rel="stylesheet">
</head>
<body class="vf-body bg-light min-vh-100 d-flex flex-column">
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container">
            @php
                $msg = isset($exception) ? trim((string) $exception->getMessage()) : '';
                if ($msg === '') {
                    $msg = 'Acesso negado.';
                }
                $painelEmpresa = auth()->check() && request()->is('empresa*');
            @endphp
            <div class="vf-card p-4 p-md-5 text-center mx-auto" style="max-width: 28rem;">
                <p class="text-muted small mb-3 mb-md-4">{{ $msg }}</p>
                @if ($painelEmpresa)
                    {{-- Mesma ideia do topo "Ver vitrine": só a ação principal, um botão --}}
                    <a href="{{ route('empresa.dashboard') }}" class="btn btn-sm btn-outline-primary px-4">
                        <i class="bi bi-speedometer2 me-1"></i>Voltar ao painel
                    </a>
                @else
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="{{ url('/') }}" class="btn btn-primary">Ir ao site</a>
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Voltar</button>
                    </div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
