<footer class="border-top bg-white mt-auto py-4">
    <div class="container small text-muted text-center">
        <p class="mb-1 d-flex align-items-center justify-content-center gap-2 flex-wrap">
            @include('partials.icons.motorbike-delivery', ['class' => 'text-primary flex-shrink-0', 'size' => 22])
            <span>Entrega em até 45 min · Pagamento na entrega ou online</span>
        </p>
        <a href="{{ route('site.home') }}" class="link-secondary">{{ config('app.name') }}</a>
    </div>
</footer>
