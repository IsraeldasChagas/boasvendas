@extends('layouts.site')

@section('title', 'Contato')

@section('content')
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-5">
                <h1 class="fw-bold mb-3">Fale conosco</h1>
                <p class="text-muted">Formulário apenas visual nesta versão — sem envio de e-mail.</p>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><i class="bi bi-envelope text-primary me-2"></i>contato@vendaffacil.com.br</li>
                    <li class="mb-2"><i class="bi bi-whatsapp text-success me-2"></i>(11) 99999-0000</li>
                    <li><i class="bi bi-geo-alt text-danger me-2"></i>São Paulo, SP — Brasil</li>
                </ul>
            </div>
            <div class="col-lg-7">
                <div class="vf-card p-4">
                    <form action="#" method="post" onsubmit="return false;">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" placeholder="Seu nome" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-mail</label>
                                <input type="email" class="form-control" placeholder="voce@email.com" disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mensagem</label>
                                <textarea class="form-control" rows="4" placeholder="Como podemos ajudar?" disabled></textarea>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-primary" disabled>Enviar (demo)</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
