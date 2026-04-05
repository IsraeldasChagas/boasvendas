@props(['type' => 'info', 'dismissible' => false])
@php
    $map = [
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];
    $cls = $map[$type] ?? 'alert-info';
@endphp
<div class="alert {{ $cls }} {{ $dismissible ? 'alert-dismissible fade show' : '' }} mb-3" role="alert">
    {{ $slot }}
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    @endif
</div>
