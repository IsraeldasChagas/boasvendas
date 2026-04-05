<footer class="vf-footer-site mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="fw-bold text-white mb-2"><i class="bi bi-bag-heart-fill me-1"></i>{{ config('app.name') }}</div>
                <p class="small mb-3">Delivery, cardápio digital e gestão para quem vende na rua e online.</p>
                <div class="small">
                    <div class="text-white-50 text-uppercase fw-semibold mb-1">Contato</div>
                    <a href="mailto:contato@boasvendas.com.br" class="link-light link-underline-opacity-0 d-block">contato@boasvendas.com.br</a>
                    <span class="text-white-50 d-block">(11) 99999-0000 · WhatsApp</span>
                    <a href="{{ route('site.contato') }}" class="link-light link-underline-opacity-0 d-inline-block mt-1">Fale conosco</a>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-white-50 small text-uppercase fw-semibold mb-2">Produto</div>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('site.planos') }}" class="link-light link-underline-opacity-0">Planos</a></li>
                    <li><a href="{{ route('site.sobre') }}" class="link-light link-underline-opacity-0">Sobre</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-2">
                <div class="text-white-50 small text-uppercase fw-semibold mb-2">Conta</div>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('login') }}" class="link-light link-underline-opacity-0">Login</a></li>
                    <li><a href="{{ route('auth.cadastro-empresa') }}" class="link-light link-underline-opacity-0">Cadastro</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <div class="text-white-50 small text-uppercase fw-semibold mb-2">Áreas do sistema</div>
                <ul class="list-unstyled small">
                    <li><a href="{{ route('empresa.dashboard') }}" class="link-light link-underline-opacity-0">Painel empresa</a></li>
                    <li><a href="{{ route('admin.dashboard') }}" class="link-light link-underline-opacity-0">Painel master</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary opacity-25 my-4">
        <p class="small text-center mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. Demonstração front-end.</p>
    </div>
</footer>
