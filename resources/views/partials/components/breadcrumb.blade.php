@props(['items' => []])
@if (count($items))
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            @foreach ($items as $i => $item)
                @if ($i === count($items) - 1)
                    <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                @else
                    <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
