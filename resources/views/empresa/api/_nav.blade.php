@php
    $apiNav = [
        ['route' => 'empresa.api.status', 'label' => 'Status', 'match' => 'empresa.api.status'],
        ['route' => 'empresa.api.tokens', 'label' => 'Tokens', 'match' => 'empresa.api.tokens'],
        ['route' => 'empresa.api.aplicacoes', 'label' => 'Aplicações', 'match' => 'empresa.api.aplicacoes'],
        ['route' => 'empresa.api.logs', 'label' => 'Logs', 'match' => 'empresa.api.logs'],
        ['route' => 'empresa.api.ambiente', 'label' => 'Ambiente', 'match' => 'empresa.api.ambiente'],
    ];
@endphp
<ul class="nav nav-pills flex-wrap gap-1 mb-4">
    @foreach ($apiNav as $item)
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
        </li>
    @endforeach
</ul>
