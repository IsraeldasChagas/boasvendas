@extends('layouts.publico')

@section('title', 'Fidelidade — '.$empresa->nome)

@section('content')
    <div class="container" style="max-width: 32rem;">
        <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="vf-badge bg-secondary-subtle text-secondary text-decoration-none">
                Cardápio online
            </a>
        </div>

        <h1 class="h4 fw-bold mb-1">{{ $programa?->nome_exibicao ?? 'Cartão fidelidade' }}</h1>
        <p class="text-muted small mb-4">{{ $empresa->nome }}</p>

        @if (! $programa || ! $programa->ativo)
            <div class="alert alert-secondary">
                Esta loja ainda não ativou o programa de fidelidade ou está em configuração.
            </div>
            <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-outline-primary">Voltar à vitrine</a>
        @else
            <div class="vf-card p-4 mb-4">
                <p class="small text-muted mb-3">
                    A cada <strong>{{ $programa->pedidos_meta }}</strong> compras contabilizadas pela loja, você ganha:
                    <strong>{{ $programa->resumoRecompensa() }}</strong>.
                    @if ($programa->texto_recompensa)
                        <span class="d-block mt-2">{{ $programa->texto_recompensa }}</span>
                    @endif
                </p>

                <div class="border-bottom pb-4 mb-4">
                    <h2 class="h6 fw-bold mb-3">Cadastrar ou atualizar meu cartão</h2>
                    <p class="small text-muted mb-3">Informe o telefone (WhatsApp), CPF e e-mail — use o <strong>mesmo telefone das suas compras</strong> na loja. Os selos são contados pela loja a cada compra.</p>
                    <form action="{{ route('publico.fidelidade.cadastrar', ['slug' => $slug]) }}" method="post" class="mb-0">
                        @csrf
                        <label class="form-label small fw-semibold" for="cad-tel">Telefone / WhatsApp</label>
                        <input type="tel" name="cadastro_telefone" id="cad-tel" value="{{ old('cadastro_telefone') }}"
                               class="form-control mb-2 @error('cadastro_telefone') is-invalid @enderror" placeholder="(69) 99999-0000" autocomplete="tel" required maxlength="32">
                        @error('cadastro_telefone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <label class="form-label small fw-semibold" for="cad-cpf">CPF</label>
                        <input type="text" name="cadastro_cpf" id="cad-cpf" value="{{ old('cadastro_cpf') }}"
                               class="form-control mb-2 @error('cadastro_cpf') is-invalid @enderror" placeholder="000.000.000-00" inputmode="numeric" autocomplete="off" required maxlength="18">
                        @error('cadastro_cpf')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <label class="form-label small fw-semibold" for="cad-email">E-mail</label>
                        <input type="email" name="cadastro_email" id="cad-email" value="{{ old('cadastro_email') }}"
                               class="form-control @error('cadastro_email') is-invalid @enderror" placeholder="seu@email.com" autocomplete="email" required maxlength="255">
                        @error('cadastro_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-success w-100 mt-3">Salvar cadastro</button>
                    </form>
                </div>

                <h2 class="h6 fw-bold mb-3">Ver meus selos</h2>

                @if ($fidelidade_otp_pending ?? false)
                    @php
                        $pend = session('fidelidade_otp_pending', []);
                        $telPend = is_array($pend) ? (string) ($pend['tel_norm'] ?? '') : '';
                        $suf = strlen($telPend) >= 4 ? substr($telPend, -4) : '****';
                        $canalOtp = is_array($pend) ? (string) ($pend['canal'] ?? '') : '';
                        $otpPorEmail = $canalOtp === \App\Services\FidelidadeOtpEntrega::CANAL_EMAIL;
                    @endphp
                    @if ($otpPorEmail)
                        <p class="small text-muted mb-3">O envio pelo <strong>WhatsApp</strong> não está disponível no momento; enviamos o código de <strong>6 dígitos</strong> para o <strong>e-mail do seu cadastro</strong> nesta loja (confira spam/lixeira). Digite o código abaixo.</p>
                    @else
                        <p class="small text-muted mb-3">Digite abaixo o <strong>código de 6 dígitos</strong> que enviamos ao <strong>WhatsApp</strong> do número que termina em <strong>***{{ $suf }}</strong>. Se não for esse número, use <strong>Usar outro telefone</strong>.</p>
                    @endif
                    <form action="{{ route('publico.fidelidade.verificar-codigo', ['slug' => $slug]) }}" method="post" class="mb-3">
                        @csrf
                        @if ($otpPorEmail)
                            <p class="small mb-2 text-muted">Código enviado por <strong>e-mail</strong> (cartão cadastrado com o telefone ***{{ $suf }}).</p>
                        @else
                            <p class="small mb-2 text-muted">Código enviado ao <strong>WhatsApp</strong> (número com final ***{{ $suf }}).</p>
                        @endif
                        <label class="form-label small fw-semibold" for="fid-codigo">Código de 6 dígitos</label>
                        <input type="text" name="codigo" id="fid-codigo" value="{{ old('codigo') }}" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                               class="form-control @error('codigo') is-invalid @enderror" placeholder="000000" required>
                        @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-primary w-100 mt-3">Confirmar e ver selos</button>
                    </form>
                    <div class="d-flex flex-column gap-2">
                        <form action="{{ route('publico.fidelidade.reenviar-codigo', ['slug' => $slug]) }}" method="post" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100">{{ $otpPorEmail ? 'Reenviar código por e-mail' : 'Reenviar código no WhatsApp' }}</button>
                        </form>
                        <form action="{{ route('publico.fidelidade.cancelar-otp', ['slug' => $slug]) }}" method="post" class="mb-0">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm text-muted p-0">Usar outro telefone</button>
                        </form>
                    </div>
                @else
                    <p class="small text-muted mb-3">Digite seu <strong>celular</strong> (o mesmo do cadastro e das compras) e clique em <strong>Enviar código no WhatsApp</strong>. Depois informe o código para ver seus selos.</p>
                    <form action="{{ route('publico.fidelidade.solicitar-codigo', ['slug' => $slug]) }}" method="post" class="mb-0">
                        @csrf
                        <label class="form-label small fw-semibold" for="tel-fid">Seu celular</label>
                        <input type="tel" name="telefone" id="tel-fid" value="{{ old('telefone') }}"
                               class="form-control @error('telefone') is-invalid @enderror" placeholder="(11) 98888-7777" autocomplete="tel" required>
                        @error('telefone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-primary w-100 mt-3">Enviar código no WhatsApp</button>
                    </form>
                @endif
            </div>

            @if ($mostrar_progresso_selos ?? false)
                @if ($telefone_selos_mascara ?? false)
                    <p class="small text-muted mb-2">Mostrando selos do número <strong>{{ $telefone_selos_mascara }}</strong>.</p>
                @endif
                @if ($cartao)
                    @php
                        $meta = $programa->pedidos_meta;
                        $filled = min((int) $cartao->selos, $meta);
                        $cheio = $cartao->selos >= $meta;
                    @endphp
                    <div class="vf-card p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">Seu progresso</h2>
                        <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                            @for ($i = 1; $i <= $meta; $i++)
                                <div class="rounded-circle d-flex align-items-center justify-content-center border {{ $i <= $filled ? 'bg-success text-white border-success' : 'bg-light text-muted border-secondary-subtle' }}"
                                     style="width: 2.25rem; height: 2.25rem; font-size: 0.75rem;">
                                    @if ($i <= $filled)
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        {{ $i }}
                                    @endif
                                </div>
                            @endfor
                        </div>
                        <p class="text-center small mb-0">
                            <strong>{{ $cartao->selos }}</strong> selo(s) · meta <strong>{{ $meta }}</strong>
                        </p>
                        @if ($cheio)
                            <div class="alert alert-success mt-3 mb-0 small">
                                <i class="bi bi-gift me-1"></i>Você completou a meta! Na próxima visita ou pedido, avise a loja para usar sua recompensa.
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-info small mb-0">
                        Ainda não há selos neste telefone. Após sua primeira compra contabilizada pela loja, seus selos aparecerão aqui.
                    </div>
                @endif
            @endif

            <a href="{{ route('publico.loja', ['slug' => $slug]) }}" class="btn btn-link ps-0">← Continuar comprando</a>
        @endif
    </div>
@endsection

@push('scripts')
    @if ($programa && $programa->ativo)
        <script>
            (function () {
                var el = document.getElementById('cad-cpf');
                if (!el) return;
                function digits(s) { return String(s || '').replace(/\D+/g, ''); }
                function fmt(d) {
                    d = digits(d).slice(0, 11);
                    if (d.length <= 3) return d;
                    if (d.length <= 6) return d.slice(0, 3) + '.' + d.slice(3);
                    if (d.length <= 9) return d.slice(0, 3) + '.' + d.slice(3, 6) + '.' + d.slice(6);
                    return d.slice(0, 3) + '.' + d.slice(3, 6) + '.' + d.slice(6, 9) + '-' + d.slice(9);
                }
                el.addEventListener('input', function () { el.value = fmt(el.value); });
            })();
        </script>
    @endif
@endpush
