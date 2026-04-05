@extends('layouts.site')

@section('title', 'Organize vendas, pedidos e fiado no celular')

@section('content')
    {{-- 1. HERO --}}
    <section class="vf-site-hero py-5">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-white bg-opacity-20 text-white border border-white border-opacity-25">Para quem vende de verdade</span>
                    </div>
                    <h1 class="display-5 fw-bold mb-3 lh-sm">Pare de perder vendas e dinheiro no seu negócio</h1>
                    <p class="lead text-white-50 mb-4">Controle seus pedidos, vendas e fiado em um único sistema simples que funciona no celular — feito para quem vende na rua, no delivery e no dia a dia.</p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <a href="{{ route('publico.loja', ['slug' => 'demo']) }}" class="btn btn-light btn-lg px-4 fw-semibold shadow">Testar agora</a>
                        <a href="{{ route('auth.cadastro-empresa') }}" class="btn btn-outline-light btn-lg px-4 fw-semibold">Começar grátis</a>
                    </div>
                    <p class="small text-white-50 mb-3">Ideal para:</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach (['Vendedor de rua', 'Churrasquinho', 'Lanchonete', 'Açaí', 'Trufas', 'Delivery', 'Fiado / consignado'] as $tag)
                            <span class="badge rounded-pill bg-white bg-opacity-10 text-white border border-white border-opacity-20">{{ $tag }}</span>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap gap-4 mt-4 pt-2 small text-white-50">
                        <div><i class="bi bi-check-circle-fill text-success me-1"></i>Sem complicação</div>
                        <div><i class="bi bi-check-circle-fill text-success me-1"></i>No bolso, onde você estiver</div>
                        <div><i class="bi bi-check-circle-fill text-success me-1"></i>Menos erro, mais lucro</div>
                    </div>
                </div>
                <div class="col-lg-6 bv-mockup-wrap">
                    <div class="bv-mockup-phone">
                        <div class="bv-mockup-notch"></div>
                        <div class="bv-mockup-screen text-dark p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <strong class="small">{{ config('app.name') }}</strong>
                                <span class="badge bg-success-subtle text-success small">Aberto</span>
                            </div>
                            <div class="small text-muted mb-2">Hoje</div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-primary-subtle">
                                        <span class="text-muted small d-block">Pedidos</span>
                                        <strong class="fs-5 text-primary">47</strong>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-success-subtle">
                                        <span class="text-muted small d-block">Lucro do dia</span>
                                        <strong class="fs-5 text-success">R$ 312</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="small fw-semibold mb-2">Últimos pedidos</div>
                            <ul class="list-unstyled small mb-0">
                                <li class="d-flex justify-content-between py-2 border-bottom"><span>2x Combo + refri</span><span class="text-success">R$ 28</span></li>
                                <li class="d-flex justify-content-between py-2 border-bottom"><span>Açaí 500ml</span><span class="text-success">R$ 18</span></li>
                                <li class="d-flex justify-content-between py-2 border-bottom"><span>Fiado — Maria</span><span class="text-warning">Anotado</span></li>
                                <li class="d-flex justify-content-between py-2"><span>Delivery #104</span><span class="badge bg-primary-subtle text-primary">A caminho</span></li>
                            </ul>
                            <div class="mt-3 pt-2 border-top d-grid">
                                <button type="button" class="btn btn-primary btn-sm" disabled>Novo pedido</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. DORES --}}
    <section id="dores" class="bv-landing-section py-5 bg-white">
        <div class="container">
            <div class="row justify-content-center text-center mb-5">
                <div class="col-lg-8">
                    <span class="text-danger fw-semibold text-uppercase small">Se isso acontece com você…</span>
                    <h2 class="fw-bold mt-2 mb-3">Seu negócio não pode depender só da cabeça e do caderno</h2>
                    <p class="text-muted mb-0">Quem vende na correria sabe: um detalhe esquecido vira dinheiro perdido. Veja se você se identifica:</p>
                </div>
            </div>
            <div class="row g-4">
                @foreach ([
                    ['icon' => 'bi-person-x', 't' => 'Você esquece quem ficou devendo?', 'd' => 'Fiado no boca a boca vira confusão. Quando não anota direito, o prejuízo aparece no fim do mês.'],
                    ['icon' => 'bi-chat-dots', 't' => 'Perde pedidos no WhatsApp?', 'd' => 'Mensagem some, cliente espera e você perde a venda — ou entrega errado e perde a confiança.'],
                    ['icon' => 'bi-currency-dollar', 't' => 'Não sabe quanto lucrou no dia?', 'd' => 'Você vende, mas no fim do dia não tem clareza do que entrou, do que saiu e do que ainda está para receber.'],
                    ['icon' => 'bi-journal-x', 't' => 'Anota tudo no caderno e se perde?', 'd' => 'Folhas soltas, rasuras e números no meio do texto viram dor de cabeça na hora de cobrar ou repor estoque.'],
                    ['icon' => 'bi-graph-down-arrow', 't' => 'Não tem controle do que vendeu?', 'd' => 'Sem visão do que mais sai, fica difícil saber o que comprar de novo e o que realmente dá lucro.'],
                    ['icon' => 'bi-emoji-frown', 't' => 'Cansaço de “desorganizado” parece normal?', 'd' => 'Não precisa ser. Ter tudo no celular deixa você mais leve e o cliente com mais confiança.'],
                ] as $pain)
                    <div class="col-md-6 col-lg-4">
                        <div class="vf-card p-4 h-100 bv-pain-card">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 bg-danger-subtle text-danger p-3"><i class="bi {{ $pain['icon'] }} fs-4"></i></div>
                                <div>
                                    <h3 class="h6 fw-bold mb-2">{{ $pain['t'] }}</h3>
                                    <p class="text-muted small mb-0">{{ $pain['d'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 3. SOLUÇÃO --}}
    <section id="solucao" class="bv-landing-section py-5" style="background: linear-gradient(180deg, var(--vf-body) 0%, #fff 100%);">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="text-primary fw-semibold text-uppercase small">A solução</span>
                    <h2 class="fw-bold mt-2 mb-4">Com o {{ config('app.name') }} você organiza tudo em poucos cliques</h2>
                    <p class="lead text-muted">É um sistema pensado para <strong>pequeno empreendedor</strong>: linguagem simples, telas claras e foco no que importa — <strong>vender mais e perder menos</strong>.</p>
                    <ul class="list-unstyled mt-4">
                        @foreach ([
                            'Pedidos organizados (balcão, mesa ou delivery)',
                            'Cardápio digital para o cliente ver e pedir',
                            'Controle de fiado: quem deve, quanto e quando',
                            'Venda externa e consignado sem bagunça',
                            'Financeiro que mostra entradas, saídas e saldo',
                            'Relatórios fáceis: fim do dia com resposta na mão',
                        ] as $item)
                            <li class="d-flex gap-3 mb-3">
                                <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0"></i>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="vf-card p-4 p-lg-5 border-primary border-opacity-25 shadow-sm">
                        <div class="text-center mb-4">
                            <i class="bi bi-lightning-charge-fill text-warning fs-1"></i>
                        </div>
                        <blockquote class="text-center mb-0">
                            <p class="fs-5 fw-semibold mb-3">“Eu só queria parar de misturar papel com WhatsApp. Hoje sei o que vendi e quem me deve — sem virar contador.”</p>
                            <footer class="text-muted small">— Falta que a gente ouve todo dia de quem vende na prática</footer>
                        </blockquote>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. COMO FUNCIONA --}}
    <section id="como-funciona" class="bv-landing-section py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <span class="text-primary fw-semibold text-uppercase small">Simples assim</span>
                <h2 class="fw-bold mt-2">Como funciona</h2>
                <p class="text-muted col-lg-7 mx-auto mb-0">Quatro passos. Nada de manual gigante nem curso.</p>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach ([
                    ['n' => 1, 't' => 'Cadastre seus produtos', 'd' => 'Preço, foto (se quiser) e nome. Seu cardápio fica pronto para mostrar e vender.'],
                    ['n' => 2, 't' => 'Receba pedidos ou registre vendas', 'd' => 'Na loja, na rua ou pelo link — tudo registrado no mesmo lugar.'],
                    ['n' => 3, 't' => 'Controle seu dinheiro automaticamente', 'd' => 'O sistema soma o dia, separa fiado e ajuda você a enxergar o caixa.'],
                    ['n' => 4, 't' => 'Veja seu lucro no fim do dia', 'd' => 'Relatório direto: o que entrou, o que ficou para depois e o que mais vendeu.'],
                ] as $step)
                    <div class="col-md-6 col-xl-3">
                        <div class="vf-card p-4 h-100 text-center">
                            <div class="bv-step-num mx-auto mb-3">{{ $step['n'] }}</div>
                            <h3 class="h5 fw-bold mb-2">{{ $step['t'] }}</h3>
                            <p class="text-muted small mb-0">{{ $step['d'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 5. BENEFÍCIOS --}}
    <section id="beneficios" class="bv-landing-section py-5 bg-primary-subtle">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <h2 class="fw-bold mb-3">Você passa a ter controle do seu negócio de verdade</h2>
                    <p class="text-muted mb-0">Não é sobre “tecnologia”. É sobre <strong>tranquilidade na hora de dormir</strong> e <strong>respeito no bolso</strong>. O {{ config('app.name') }} existe para isso.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row g-3">
                        @foreach ([
                            ['icon' => 'bi-grid-1x2', 't' => 'Mais organização', 'd' => 'Pedidos, produtos e clientes no mesmo painel.'],
                            ['icon' => 'bi-graph-up-arrow', 't' => 'Mais vendas', 'd' => 'Menos erro no pedido = mais cliente satisfeito e voltando.'],
                            ['icon' => 'bi-shield-check', 't' => 'Menos prejuízo', 'd' => 'Fiado e estoque com rastro. Você cobra com educação e firmeza.'],
                            ['icon' => 'bi-sliders', 't' => 'Controle total', 'd' => 'Sabe o que aconteceu hoje — não só no “achismo”.'],
                            ['icon' => 'bi-award', 't' => 'Negócio mais profissional', 'd' => 'Cliente percebe quando você é organizado.'],
                            ['icon' => 'bi-phone', 't' => 'No celular', 'd' => 'Acompanhe de casa, do ponto ou da cozinha.'],
                        ] as $b)
                            <div class="col-md-6">
                                <div class="d-flex gap-3 p-3 rounded-3 bg-white shadow-sm h-100">
                                    <div class="text-primary fs-4"><i class="bi {{ $b['icon'] }}"></i></div>
                                    <div>
                                        <div class="fw-bold">{{ $b['t'] }}</div>
                                        <div class="small text-muted">{{ $b['d'] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 6. MÓDULOS --}}
    <section id="modulos" class="bv-landing-section py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <span class="text-success fw-semibold text-uppercase small">Tudo integrado</span>
                <h2 class="fw-bold mt-2">Módulos do sistema</h2>
                <p class="text-muted col-lg-7 mx-auto mb-0">Monte o que faz sentido para o seu tamanho de negócio — do food truck ao delivery fixo.</p>
            </div>
            <div class="row g-4">
                @foreach ([
                    ['icon' => 'bi-receipt', 't' => 'Pedidos', 'd' => 'Registre, acompanhe status e não deixe nada para trás.'],
                    ['icon' => 'bi-menu-button-wide', 't' => 'Cardápio digital', 'd' => 'Mostre seus produtos com preço claro e link para pedir.'],
                    ['icon' => 'bi-wallet2', 't' => 'Financeiro', 'd' => 'Entradas, saídas e visão do que sobrou no caixa.'],
                    ['icon' => 'bi-truck', 't' => 'Venda externa', 'd' => 'Rotas, pontos e acertos para quem vende fora da loja.'],
                    ['icon' => 'bi-cash-stack', 't' => 'Fiado', 'd' => 'Quem deve, quanto e histórico — sem conversa constrangedora à toa.'],
                    ['icon' => 'bi-bar-chart-line', 't' => 'Relatórios', 'd' => 'Resumo do dia e do que mais gira no seu negócio.'],
                ] as $m)
                    <div class="col-md-6 col-lg-4">
                        <div class="vf-card p-4 h-100 vf-product-card">
                            <div class="icon-wrap bg-success-subtle text-success rounded-3 d-inline-flex p-3 mb-3">
                                <i class="bi {{ $m['icon'] }} fs-4"></i>
                            </div>
                            <h3 class="h5 fw-bold">{{ $m['t'] }}</h3>
                            <p class="text-muted small mb-0">{{ $m['d'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 7. DEPOIMENTOS --}}
    <section id="depoimentos" class="bv-landing-section py-5" style="background: var(--vf-body);">
        <div class="container">
            <div class="text-center mb-5">
                <span class="text-primary fw-semibold text-uppercase small">Confiança</span>
                <h2 class="fw-bold mt-2">Quem organizou, não voltou atrás</h2>
                <p class="text-muted mb-0">Depoimentos de demonstração — mas a história é real para milhares de pequenos negócios no Brasil.</p>
            </div>
            <div class="row g-4">
                @foreach ([
                    ['nome' => 'Carla M.', 'neg' => 'Açaí e sorvetes — Belo Horizonte', 'txt' => 'Depois que comecei a usar, nunca mais me perdi nas vendas do fim de semana. O fiado estava virando pesadelo; agora está na tela.'],
                    ['nome' => 'Renato S.', 'neg' => 'Churrasquinho móvel', 'txt' => 'Na rua não dá para ficar com caderno molhado. Pelo celular eu lanço na hora e já sei se o dia valeu a pena.'],
                    ['nome' => 'Jéssica L.', 'neg' => 'Trufas e doces', 'txt' => 'Cliente vê o cardápio pelo link e eu não fico repetindo preço o dia todo. Parece coisa de loja grande, mas é o meu quintal.'],
                ] as $dep)
                    <div class="col-md-4">
                        <div class="vf-card p-4 h-100">
                            <div class="text-warning mb-2"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                            <p class="mb-3">“{{ $dep['txt'] }}”</p>
                            <div class="small">
                                <strong>{{ $dep['nome'] }}</strong>
                                <div class="text-muted">{{ $dep['neg'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 8. PLANOS --}}
    <section id="planos" class="bv-landing-section py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Planos que cabem no seu bolso</h2>
                <p class="text-muted mb-0">Valores fictícios para demonstração — escolha o que combina com a sua fase.</p>
            </div>
            <div class="row g-4 justify-content-center align-items-stretch">
                <div class="col-md-6 col-lg-4">
                    <div class="vf-card p-4 h-100 bv-pricing-card d-flex flex-column">
                        <h3 class="h5 fw-bold">Básico</h3>
                        <p class="text-muted small">Para começar sem medo.</p>
                        <div class="display-6 fw-bold text-primary my-3">R$ 49,00<small class="fs-6 fw-normal text-muted">/mês</small></div>
                        <ul class="list-unstyled small flex-grow-1 mb-4">
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Pedidos</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Cardápio digital</li>
                            <li class="mb-2 text-muted"><i class="bi bi-dash me-2"></i>Financeiro completo</li>
                            <li class="text-muted"><i class="bi bi-dash me-2"></i>Módulos avançados</li>
                        </ul>
                        <a href="{{ route('auth.cadastro-empresa') }}" class="btn btn-outline-primary w-100">Começar</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="vf-card p-4 h-100 bv-pricing-card bv-pricing-featured d-flex flex-column position-relative">
                        <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary px-3">Mais escolhido</span>
                        <h3 class="h5 fw-bold mt-2">Intermediário</h3>
                        <p class="text-muted small">O equilíbrio entre preço e poder.</p>
                        <div class="display-6 fw-bold text-primary my-3">R$ 99,00<small class="fs-6 fw-normal text-muted">/mês</small></div>
                        <ul class="list-unstyled small flex-grow-1 mb-4">
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Tudo do Básico</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Pedidos + financeiro</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Relatórios do dia</li>
                            <li class="text-muted"><i class="bi bi-dash me-2"></i>Venda externa completa</li>
                        </ul>
                        <a href="{{ route('auth.cadastro-empresa') }}" class="btn btn-primary w-100 fw-semibold">Quero esse</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="vf-card p-4 h-100 bv-pricing-card d-flex flex-column">
                        <h3 class="h5 fw-bold">Completo</h3>
                        <p class="text-muted small">Para quem quer tudo em uma assinatura.</p>
                        <div class="display-6 fw-bold text-success my-3">R$ 290,00<small class="fs-6 fw-normal text-muted">/mês</small></div>
                        <ul class="list-unstyled small flex-grow-1 mb-4">
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Tudo do Intermediário</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Venda externa / consignado</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Fiado e cobranças</li>
                            <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>Relatórios completos</li>
                        </ul>
                        <a href="{{ route('auth.cadastro-empresa') }}" class="btn btn-success w-100 fw-semibold">Falar com vendas</a>
                    </div>
                </div>
            </div>
            <p class="text-center small text-muted mt-4 mb-0">Sem fidelidade abusiva na demonstração. Cancele quando quiser.</p>
        </div>
    </section>

    {{-- 9. CTA FINAL --}}
    <section class="py-5">
        <div class="container">
            <div class="bv-cta-final p-5 p-lg-5 text-center position-relative">
                <h2 class="fw-bold mb-3 position-relative">Comece agora e organize seu negócio hoje mesmo</h2>
                <p class="text-white-50 mb-4 col-lg-8 mx-auto position-relative">Cadastro rápido. Você pode testar com a loja demo e ver se o jeito {{ config('app.name') }} combina com o seu ritmo.</p>
                <a href="{{ route('publico.loja', ['slug' => 'demo']) }}" class="btn btn-light btn-lg fw-bold px-5 py-3 text-uppercase position-relative shadow">Testar agora</a>
            </div>
        </div>
    </section>
@endsection
