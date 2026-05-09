{{--
  Moto / entrega — Tabler Icons motorbike (outline), Apache 2.0.
  @var string|null $class
  @var int|string|null $size px
--}}
@php
    $icoClass = $class ?? '';
    $icoSize = $size ?? 20;
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $icoSize }}" height="{{ $icoSize }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="{{ $icoClass }}" aria-hidden="true">
    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
    <path d="M5 16m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/>
    <path d="M19 16m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/>
    <path d="M7.5 14h5l4 -4h-10.5m1.5 4l4 -4"/>
    <path d="M13 6h2l1.5 3l2 4"/>
</svg>
