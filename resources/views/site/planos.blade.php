@extends('layouts.site')

@section('title', 'Planos')

@section('content')
    <div class="bg-primary-subtle py-5 border-bottom">
        <div class="container text-center">
            <h1 class="fw-bold">Planos simples, escala quando precisar</h1>
            <p class="text-muted mb-0">Valores ilustrativos — sem cobrança real nesta demo.</p>
        </div>
    </div>
    <div class="container py-5">
        <div class="row g-4 justify-content-center">
            @php
                $planos = [
                    ['nome' => 'Essencial', 'preco' => '49', 'dest' => false, 'itens' => ['Loja pública', 'Até 200 produtos', 'Suporte por e-mail']],
                    ['nome' => 'Crescimento', 'preco' => '99', 'dest' => true, 'itens' => ['Tudo do Essencial', 'Venda externa / fiado', 'Relatórios avançados', '2 filiais']],
                    ['nome' => 'Franquia', 'preco' => '249', 'dest' => false, 'itens' => ['Multiunidade', 'API (futuro)', 'Onboarding dedicado', 'SLA suporte']],
                ];
            @endphp
            @foreach ($planos as $p)
                <div class="col-lg-4">
                    <div class="vf-card p-4 h-100 {{ $p['dest'] ? 'border-primary border-2 shadow' : '' }}">
                        @if ($p['dest'])
                            <span class="vf-badge bg-primary text-white mb-2">Mais escolhido</span>
                        @endif
                        <h2 class="h4 fw-bold">{{ $p['nome'] }}</h2>
                        <p class="display-6 fw-bold text-primary mb-0">R$ {{ $p['preco'] }}<small class="fs-6 text-muted fw-normal">/mês</small></p>
                        <hr>
                        <ul class="list-unstyled small mb-4">
                            @foreach ($p['itens'] as $it)
                                <li class="mb-2"><i class="bi bi-check2 text-success me-2"></i>{{ $it }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('auth.cadastro-empresa') }}" class="btn w-100 {{ $p['dest'] ? 'btn-primary' : 'btn-outline-primary' }}">Assinar (demo)</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
