@extends('layouts.site')

@section('title', 'Organize vendas, pedidos e fiado no celular')

@section('content')
    {{-- 1. HERO --}}
    <section class="vf-site-hero py-5">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-white text-primary border-0 px-3 py-2">Para quem vende de verdade</span>
                    </div>
                    <h1 class="display-5 fw-bold mb-3 lh-sm">Pare de perder vendas e dinheiro no seu negócio</h1>
                    <p class="lead text-white-50 mb-4">Controle seus pedidos, vendas e fiado em um único sistema simples que funciona no celular — feito para quem vende na rua, no delivery e no dia a dia.</p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        @php $demoSlug = \App\Models\Empresa::slugVitrineDemo(); @endphp
                        <a href="{{ $demoSlug ? route('publico.loja', ['slug' => $demoSlug]) : route('auth.cadastro-empresa') }}" class="btn btn-light btn-lg px-4 fw-semibold shadow">Testar agora</a>
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
                    <div class="bv-devices-stage" aria-hidden="true">
                        {{-- Notebook --}}
                        <div class="bv-laptop">
                            <div class="bv-laptop-lid">
                                <div class="bv-laptop-bezel">
                                    <div class="bv-laptop-camera"></div>
                                    <div class="bv-device-screens">
                                        {{-- Tela 1: Painel empresa --}}
                                        <div class="bv-device-screen bv-device-screen--1 text-dark">
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <strong class="small">{{ config('app.name') }} · Empresa</strong>
                                                <span class="badge bg-success-subtle text-success" style="font-size:0.65rem">Aberto</span>
                                            </div>
                                            <div class="small text-muted mb-1">Painel de hoje</div>
                                            <div class="row g-1 mb-2">
                                                <div class="col-4">
                                                    <div class="p-1 rounded bg-primary-subtle text-center">
                                                        <span class="text-muted d-block" style="font-size:0.55rem">Pedidos</span>
                                                        <strong class="text-primary" style="font-size:0.9rem">47</strong>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-1 rounded bg-success-subtle text-center">
                                                        <span class="text-muted d-block" style="font-size:0.55rem">Lucro</span>
                                                        <strong class="text-success" style="font-size:0.85rem">R$ 312</strong>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-1 rounded bg-warning-subtle text-center">
                                                        <span class="text-muted d-block" style="font-size:0.55rem">Fiados</span>
                                                        <strong class="text-warning" style="font-size:0.9rem">8</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="fw-semibold mb-1" style="font-size:0.7rem">Últimos pedidos</div>
                                            <ul class="list-unstyled mb-0" style="font-size:0.65rem">
                                                <li class="d-flex justify-content-between py-1 border-bottom"><span>2x Combo + refri</span><span class="text-success">R$ 28</span></li>
                                                <li class="d-flex justify-content-between py-1 border-bottom"><span>Açaí 500ml</span><span class="text-success">R$ 18</span></li>
                                                <li class="d-flex justify-content-between py-1"><span>Fiado — Maria</span><span class="text-warning">Anotado</span></li>
                                            </ul>
                                        </div>
                                        {{-- Tela 2: Delivery --}}
                                        <div class="bv-device-screen bv-device-screen--2 text-dark">
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <strong class="small"><i class="bi bi-truck me-1"></i>Delivery</strong>
                                                <span class="badge bg-primary-subtle text-primary" style="font-size:0.65rem">5 em rota</span>
                                            </div>
                                            <ul class="list-unstyled mb-0" style="font-size:0.65rem">
                                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom gap-2">
                                                    <span><span class="fw-semibold">#104</span> · Centro</span>
                                                    <span class="badge bg-primary-subtle text-primary">A caminho</span>
                                                </li>
                                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom gap-2">
                                                    <span><span class="fw-semibold">#105</span> · Jardins</span>
                                                    <span class="badge bg-warning-subtle text-warning">Preparando</span>
                                                </li>
                                                <li class="d-flex justify-content-between align-items-center py-1 border-bottom gap-2">
                                                    <span><span class="fw-semibold">#106</span> · Vila Nova</span>
                                                    <span class="badge bg-info-subtle text-info">Saiu</span>
                                                </li>
                                                <li class="d-flex justify-content-between align-items-center py-1 gap-2">
                                                    <span><span class="fw-semibold">#103</span> · Bairro Alto</span>
                                                    <span class="badge bg-success-subtle text-success">Entregue</span>
                                                </li>
                                            </ul>
                                            <div class="mt-2 p-2 rounded bg-light" style="font-size:0.6rem">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>Mapa e status em tempo real
                                            </div>
                                        </div>
                                        {{-- Tela 3: Relatórios --}}
                                        <div class="bv-device-screen bv-device-screen--3 text-dark">
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                <strong class="small"><i class="bi bi-bar-chart-line me-1"></i>Relatórios</strong>
                                                <span class="text-muted" style="font-size:0.6rem">Esta semana</span>
                                            </div>
                                            <div class="bv-fake-bars mb-2">
                                                <span style="--h:45%"></span>
                                                <span style="--h:70%"></span>
                                                <span style="--h:55%"></span>
                                                <span style="--h:90%"></span>
                                                <span style="--h:65%"></span>
                                                <span style="--h:80%"></span>
                                                <span style="--h:50%"></span>
                                            </div>
                                            <div class="row g-1" style="font-size:0.6rem">
                                                <div class="col-6"><div class="p-1 rounded bg-success-subtle"><span class="text-muted d-block">Vendas</span><strong class="text-success">R$ 4.280</strong></div></div>
                                                <div class="col-6"><div class="p-1 rounded bg-primary-subtle"><span class="text-muted d-block">Ticket médio</span><strong class="text-primary">R$ 32</strong></div></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bv-laptop-base">
                                <div class="bv-laptop-notch"></div>
                            </div>
                        </div>

                        {{-- Celular --}}
                        <div class="bv-phone-float">
                            <div class="bv-phone-frame">
                                <div class="bv-mockup-notch"></div>
                                <div class="bv-device-screens bv-device-screens--phone">
                                    <div class="bv-device-screen bv-device-screen--1 text-dark p-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong style="font-size:0.65rem">Painel</strong>
                                            <span class="badge bg-success-subtle text-success" style="font-size:0.55rem">Online</span>
                                        </div>
                                        <div class="p-2 rounded bg-primary text-white mb-2" style="font-size:0.65rem">
                                            <div class="opacity-75">Hoje</div>
                                            <strong>R$ 312</strong>
                                        </div>
                                        <div class="d-grid gap-1" style="font-size:0.6rem">
                                            <div class="p-1 rounded bg-light border">+ Novo pedido</div>
                                            <div class="p-1 rounded bg-light border">Mesas / comandas</div>
                                            <div class="p-1 rounded bg-light border">Caixa do dia</div>
                                        </div>
                                    </div>
                                    <div class="bv-device-screen bv-device-screen--2 text-dark p-2">
                                        <div class="fw-semibold mb-2" style="font-size:0.65rem"><i class="bi bi-bicycle me-1"></i>Entregas</div>
                                        <div class="p-2 rounded border mb-1" style="font-size:0.58rem">
                                            <div class="fw-semibold">#104 · João</div>
                                            <div class="text-muted">Rua das Flores, 120</div>
                                            <span class="badge bg-primary-subtle text-primary mt-1">A caminho</span>
                                        </div>
                                        <div class="p-2 rounded border" style="font-size:0.58rem">
                                            <div class="fw-semibold">#105 · Ana</div>
                                            <div class="text-muted">Av. Brasil, 890</div>
                                            <span class="badge bg-warning-subtle text-warning mt-1">Preparando</span>
                                        </div>
                                    </div>
                                    <div class="bv-device-screen bv-device-screen--3 text-dark p-2">
                                        <div class="fw-semibold mb-2" style="font-size:0.65rem"><i class="bi bi-pie-chart me-1"></i>Resumo</div>
                                        <div class="text-center py-2">
                                            <div class="text-muted" style="font-size:0.55rem">Top produto</div>
                                            <div class="fw-bold text-primary" style="font-size:0.75rem">Combo Família</div>
                                            <div class="text-success" style="font-size:0.6rem">23 vendas</div>
                                        </div>
                                        <div class="p-2 rounded bg-success-subtle text-center" style="font-size:0.6rem">
                                            Lucro da semana<br><strong>R$ 1.890</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bv-devices-labels">
                            <span class="bv-devices-label bv-devices-label--1"><i class="bi bi-shop me-1"></i>Empresa</span>
                            <span class="bv-devices-label bv-devices-label--2"><i class="bi bi-truck me-1"></i>Delivery</span>
                            <span class="bv-devices-label bv-devices-label--3"><i class="bi bi-graph-up me-1"></i>Relatórios</span>
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
                    <div class="vf-card p-4 p-lg-5 bv-quote-card">
                        <div class="text-center mb-4">
                            <i class="bi bi-lightning-charge-fill text-warning fs-1 bv-quote-bolt"></i>
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

    {{-- 5b. CARTÃO FIDELIDADE --}}
    <section id="fidelidade" class="bv-landing-section py-5" style="background: linear-gradient(160deg, #eff6ff 0%, #ecfdf5 50%, #fff 100%);">
        <div class="container">
            <div class="vf-card bv-fidelidade-card p-4 p-lg-5 border-0 shadow-sm overflow-hidden">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-lg-5">
                        <span class="text-primary fw-semibold text-uppercase small">Diferencial</span>
                        <h2 class="fw-bold mt-2 mb-3">Cartão fidelidade digital</h2>
                        <p class="text-muted mb-4">
                            Seu cliente ganha um cartão com selos/pontos a cada compra. Você configura a meta e o prêmio —
                            o {{ config('app.name') }} cuida do histórico e do resgate, sem papel rasgado nem caderno perdido.
                        </p>

                        <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Como funciona</h3>
                        <ol class="list-unstyled mb-0 bv-fidelidade-steps">
                            <li class="d-flex gap-3 mb-3">
                                <span class="bv-fidelidade-step-num">1</span>
                                <div>
                                    <div class="fw-semibold">Você cria o programa</div>
                                    <div class="small text-muted">Define quantos selos valem o prêmio e o que o cliente ganha.</div>
                                </div>
                            </li>
                            <li class="d-flex gap-3 mb-3">
                                <span class="bv-fidelidade-step-num">2</span>
                                <div>
                                    <div class="fw-semibold">Cliente recebe o cartão</div>
                                    <div class="small text-muted">Pelo link da loja, com código no WhatsApp para ver os selos com segurança.</div>
                                </div>
                            </li>
                            <li class="d-flex gap-3 mb-3">
                                <span class="bv-fidelidade-step-num">3</span>
                                <div>
                                    <div class="fw-semibold">A cada compra, você carimba</div>
                                    <div class="small text-muted">No painel: um toque e o selo entra no cartão do cliente.</div>
                                </div>
                            </li>
                            <li class="d-flex gap-3">
                                <span class="bv-fidelidade-step-num">4</span>
                                <div>
                                    <div class="fw-semibold">Meta batida? Resgata o prêmio</div>
                                    <div class="small text-muted">Histórico registrado — cliente feliz e você com controle.</div>
                                </div>
                            </li>
                        </ol>
                    </div>

                    <div class="col-lg-7">
                        <div class="row g-3">
                            {{-- Card empresa --}}
                            <div class="col-md-6">
                                <div class="bv-fidelidade-side h-100 p-4 rounded-4 bg-primary text-white position-relative overflow-hidden">
                                    <div class="bv-fidelidade-icon bv-fidelidade-icon--empresa mb-3" aria-hidden="true">
                                        <div class="bv-fidelidade-plastic">
                                            <i class="bi bi-shop-window"></i>
                                            <span class="bv-fidelidade-chip"></span>
                                            <span class="bv-fidelidade-plastic-label">EMPRESA</span>
                                        </div>
                                    </div>
                                    <h3 class="h5 fw-bold mb-3">Benefícios para a empresa</h3>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Cliente volta mais vezes para completar o cartão</span></li>
                                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Programa com regras suas — sem papel e sem esquecimento</span></li>
                                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Histórico de selos e resgates no painel</span></li>
                                        <li class="d-flex gap-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Negócio mais profissional e cliente fidelizado</span></li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Card cliente --}}
                            <div class="col-md-6">
                                <div class="bv-fidelidade-side h-100 p-4 rounded-4 bg-success text-white position-relative overflow-hidden">
                                    <div class="bv-fidelidade-icon bv-fidelidade-icon--cliente mb-3" aria-hidden="true">
                                        <div class="bv-fidelidade-plastic">
                                            <i class="bi bi-person-heart"></i>
                                            <span class="bv-fidelidade-chip"></span>
                                            <span class="bv-fidelidade-plastic-label">CLIENTE</span>
                                        </div>
                                    </div>
                                    <h3 class="h5 fw-bold mb-3">Benefícios para o cliente</h3>
                                    <ul class="list-unstyled small mb-0">
                                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Cartão digital no celular — sem plástico para esquecer</span></li>
                                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Vê os selos com código seguro no WhatsApp</span></li>
                                        <li class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Sabe exatamente quanto falta para o prêmio</span></li>
                                        <li class="d-flex gap-2"><i class="bi bi-check2-circle flex-shrink-0 mt-1"></i><span>Sente que a compra vale a pena e volta com prazer</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 5c. INTEGRAÇÃO IFOOD --}}
    <section id="ifood" class="bv-landing-section py-5 bg-white">
        <div class="container">
            <div class="vf-card bv-ifood-card p-4 p-lg-5 border-0 shadow-sm overflow-hidden">
                <div class="row align-items-center g-4 g-lg-5">
                    <div class="col-lg-5 text-center text-lg-start">
                        <span class="text-danger fw-semibold text-uppercase small">Integração</span>
                        <h2 class="fw-bold mt-2 mb-3">Conecte o {{ config('app.name') }} ao iFood</h2>
                        <p class="text-muted mb-4">
                            Receba pedidos do iFood no mesmo fluxo do seu negócio: menos tela aberta,
                            menos erro de digitação e mais tempo pra preparar e entregar.
                        </p>

                        <div class="bv-ifood-bridge d-flex align-items-center justify-content-center justify-content-lg-start gap-3 mb-2" aria-hidden="true">
                            <div class="bv-ifood-badge bv-ifood-badge--vf">
                                <i class="bi bi-bag-heart-fill"></i>
                                <span>{{ config('app.name') }}</span>
                            </div>
                            <div class="bv-ifood-link">
                                <span class="bv-ifood-dot"></span>
                                <span class="bv-ifood-dot"></span>
                                <span class="bv-ifood-dot"></span>
                                <i class="bi bi-arrow-left-right bv-ifood-arrows"></i>
                            </div>
                            <div class="bv-ifood-badge bv-ifood-badge--ifood">
                                <i class="bi bi-bag-check-fill"></i>
                                <span>iFood</span>
                            </div>
                        </div>
                        <p class="small text-muted mb-0">Pedidos, status e cardápio conversando no mesmo ritmo.</p>
                    </div>

                    <div class="col-lg-7">
                        <div class="row g-3">
                            @foreach ([
                                ['icon' => 'bi-inbox', 't' => 'Pedidos em um só lugar', 'd' => 'O pedido do iFood chega organizado junto com balcão, WhatsApp e delivery próprio.'],
                                ['icon' => 'bi-lightning-charge', 't' => 'Menos retrabalho', 'd' => 'Evita digitar de novo o que o cliente já pediu no app — menos erro e mais velocidade.'],
                                ['icon' => 'bi-graph-up-arrow', 't' => 'Mais vendas com controle', 'd' => 'Você aproveita o alcance do iFood sem perder a visão do que vendeu no dia.'],
                                ['icon' => 'bi-phone', 't' => 'Operação no celular', 'd' => 'Acompanhe status e preparação onde estiver — cozinha, balcão ou na rua.'],
                            ] as $item)
                                <div class="col-md-6">
                                    <div class="bv-ifood-benefit h-100 p-3 rounded-3">
                                        <div class="bv-ifood-benefit-icon mb-2">
                                            <i class="bi {{ $item['icon'] }}"></i>
                                        </div>
                                        <div class="fw-bold mb-1">{{ $item['t'] }}</div>
                                        <div class="small text-muted">{{ $item['d'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
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
                        <div class="vf-card p-4 h-100 vf-product-card bv-modulo-card">
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
                <a href="{{ (\App\Models\Empresa::slugVitrineDemo()) ? route('publico.loja', ['slug' => \App\Models\Empresa::slugVitrineDemo()]) : route('auth.cadastro-empresa') }}" class="btn btn-light btn-lg fw-bold px-5 py-3 text-uppercase position-relative shadow">Testar agora</a>
            </div>
        </div>
    </section>
@endsection
