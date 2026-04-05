@extends('layouts.site')

@section('title', 'Sobre')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="fw-bold mb-3">Sobre o {{ config('app.name') }}</h1>
                <p class="lead text-muted">Somos um SaaS multiempresa focado em quem vende todos os dias — na rua, no balcão ou com delivery.</p>
                <div class="vf-card p-4 my-4">
                    <h2 class="h5 fw-semibold">Nossa missão</h2>
                    <p class="mb-0 text-muted">Dar ao pequeno negócio as mesmas ferramentas visuais e organizacionais de grandes players, sem complicação técnica nesta primeira camada.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="vf-card p-3 h-100">
                            <i class="bi bi-layers text-primary fs-4"></i>
                            <h3 class="h6 fw-bold mt-2">Arquitetura preparada</h3>
                            <p class="small text-muted mb-0">Áreas separadas: site, cliente final, empresa e master admin — prontas para evoluir com API e banco.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="vf-card p-3 h-100">
                            <i class="bi bi-phone-flip text-success fs-4"></i>
                            <h3 class="h6 fw-bold mt-2">Mobile first</h3>
                            <p class="small text-muted mb-0">Interfaces responsivas pensadas para o vendedor de rua e o cliente no celular.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
