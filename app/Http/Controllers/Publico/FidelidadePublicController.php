<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use App\Services\FidelidadeOtpEntrega;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FidelidadePublicController extends Controller
{
    private const OTP_TTL_MINUTES = 10;

    private const ACESSO_TTL_MINUTES = 45;

    public function __construct(
        private readonly FidelidadeOtpEntrega $fidelidadeOtpEntrega,
    ) {}

    public function show(string $slug): View
    {
        $empresa = $this->empresaPorSlug($slug);
        $programa = $empresa->fidelidadePrograma;

        $acesso = $this->fidelidadeAcessoValido($empresa->id);
        $cartao = null;
        $otpPending = $this->otpPendenteValidoParaEmpresa($empresa->id);

        $mostrarProgressoSelos = false;
        $telefoneSelosMascara = null;

        if ($programa && $programa->ativo && ! $otpPending && $acesso !== null) {
            $norm = $acesso['tel_norm'];
            $mostrarProgressoSelos = true;
            $telefoneSelosMascara = strlen($norm) >= 4 ? '***'.substr($norm, -4) : $norm;
            $cartao = FidelidadeCartao::query()
                ->where('empresa_id', $empresa->id)
                ->where('telefone_normalizado', $norm)
                ->first();
        }

        return view('publico.fidelidade', [
            'slug' => $slug,
            'empresa' => $empresa,
            'programa' => $programa,
            'cartao' => $cartao,
            'mostrar_progresso_selos' => $mostrarProgressoSelos,
            'telefone_selos_mascara' => $telefoneSelosMascara,
            'fidelidade_otp_pending' => $otpPending,
        ]);
    }

    public function solicitarCodigo(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaPorSlug($slug);
        $programa = $empresa->fidelidadePrograma;
        if (! $programa || ! $programa->ativo) {
            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'O programa de fidelidade não está disponível nesta loja.');
        }

        $data = $request->validate([
            'telefone' => ['required', 'string', 'min:8', 'max:32'],
        ]);

        $norm = FidelidadeCartao::normalizarTelefone($data['telefone']);
        if (strlen($norm) < 8) {
            return back()
                ->withErrors(['telefone' => 'Informe um telefone válido.'])
                ->withInput();
        }

        $temCartao = FidelidadeCartao::query()
            ->where('empresa_id', $empresa->id)
            ->where('telefone_normalizado', $norm)
            ->exists();
        if (! $temCartao) {
            return back()
                ->with('warning', 'Não encontramos cartão fidelidade para este telefone nesta loja. Cadastre-se acima ou confira o número.')
                ->withInput();
        }

        $rateKey = 'fidelidade-otp-solicitar:'.$empresa->id.':'.$norm;
        if (RateLimiter::tooManyAttempts($rateKey, 4)) {
            $segundos = RateLimiter::availableIn($rateKey);

            return back()
                ->withErrors(['telefone' => 'Aguarde '.max(1, $segundos).' segundos para solicitar outro código.'])
                ->withInput();
        }
        RateLimiter::hit($rateKey, 3600);

        $codigo = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $cacheKey = $this->cacheKeyOtp($empresa->id, $norm);
        Cache::put($cacheKey, $codigo, now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::forget($this->cacheKeyFalhas($empresa->id, $norm));

        $envio = $this->fidelidadeOtpEntrega->entregar($empresa, $norm, $codigo, self::OTP_TTL_MINUTES);
        if (! $envio['ok']) {
            Cache::forget($cacheKey);
            RateLimiter::clear($rateKey);

            return back()
                ->withErrors(['telefone' => $this->mensagemFalhaEntrega($envio)])
                ->withInput();
        }

        $request->session()->put('fidelidade_otp_pending', [
            'empresa_id' => $empresa->id,
            'tel_norm' => $norm,
            'telefone_input' => $data['telefone'],
            'canal' => $envio['canal'] ?? FidelidadeOtpEntrega::CANAL_EMAIL,
        ]);

        return redirect()
            ->route('publico.fidelidade', ['slug' => $slug])
            ->with('status', $this->mensagemSucessoCodigoEnviado(
                (string) ($envio['canal'] ?? FidelidadeOtpEntrega::CANAL_EMAIL),
                false,
            ));
    }

    public function reenviarCodigo(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaPorSlug($slug);
        $programa = $empresa->fidelidadePrograma;
        if (! $programa || ! $programa->ativo) {
            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'O programa de fidelidade não está disponível nesta loja.');
        }

        $pending = session('fidelidade_otp_pending');
        if (! is_array($pending)
            || (int) ($pending['empresa_id'] ?? 0) !== (int) $empresa->id
            || ! is_string($pending['tel_norm'] ?? null)
        ) {
            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'Peça um código informando o telefone novamente.');
        }

        $norm = $pending['tel_norm'];

        $rateKey = 'fidelidade-otp-solicitar:'.$empresa->id.':'.$norm;
        if (RateLimiter::tooManyAttempts($rateKey, 4)) {
            $segundos = RateLimiter::availableIn($rateKey);

            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'Aguarde '.max(1, $segundos).' segundos para solicitar outro código.');
        }
        RateLimiter::hit($rateKey, 3600);

        $codigo = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $cacheKey = $this->cacheKeyOtp($empresa->id, $norm);
        Cache::put($cacheKey, $codigo, now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::forget($this->cacheKeyFalhas($empresa->id, $norm));

        $envio = $this->fidelidadeOtpEntrega->entregar($empresa, $norm, $codigo, self::OTP_TTL_MINUTES);
        if (! $envio['ok']) {
            Cache::forget($cacheKey);
            RateLimiter::clear($rateKey);

            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', $this->mensagemFalhaEntrega($envio));
        }

        $canal = (string) ($envio['canal'] ?? FidelidadeOtpEntrega::CANAL_EMAIL);
        $status = $this->mensagemSucessoCodigoEnviado($canal, true);

        $pending = session('fidelidade_otp_pending', []);
        if (is_array($pending)) {
            $pending['canal'] = $canal;
            $request->session()->put('fidelidade_otp_pending', $pending);
        }

        return redirect()
            ->route('publico.fidelidade', ['slug' => $slug])
            ->with('status', $status);
    }

    public function cancelarOtp(Request $request, string $slug): RedirectResponse
    {
        $this->empresaPorSlug($slug);
        $request->session()->forget('fidelidade_otp_pending');

        return redirect()
            ->route('publico.fidelidade', ['slug' => $slug]);
    }

    public function verificarCodigo(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaPorSlug($slug);
        $programa = $empresa->fidelidadePrograma;
        if (! $programa || ! $programa->ativo) {
            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'O programa de fidelidade não está disponível nesta loja.');
        }

        $pending = session('fidelidade_otp_pending');
        if (! is_array($pending)
            || (int) ($pending['empresa_id'] ?? 0) !== (int) $empresa->id
            || ! is_string($pending['tel_norm'] ?? null)
        ) {
            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'Peça um novo código antes de continuar.');
        }

        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:32'],
        ]);

        $canalEntrega = is_array($pending) && ($pending['canal'] ?? '') === FidelidadeOtpEntrega::CANAL_EMAIL
            ? FidelidadeOtpEntrega::CANAL_EMAIL
            : FidelidadeOtpEntrega::CANAL_WHATSAPP;

        $codigoDigits = preg_replace('/\D+/', '', $data['codigo']);
        if (strlen($codigoDigits) !== 6) {
            $msgCodigo = $canalEntrega === FidelidadeOtpEntrega::CANAL_EMAIL
                ? 'Informe os 6 dígitos do código recebido por e-mail (confira spam).'
                : 'Informe os 6 dígitos do código recebido no WhatsApp.';

            return back()
                ->withErrors(['codigo' => $msgCodigo])
                ->withInput();
        }

        $telNorm = $pending['tel_norm'];
        $cacheKey = $this->cacheKeyOtp($empresa->id, $telNorm);
        $falhasKey = $this->cacheKeyFalhas($empresa->id, $telNorm);

        if ((int) Cache::get($falhasKey, 0) >= 8) {
            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'Muitas tentativas incorretas. Solicite um novo código.');
        }

        $esperado = Cache::get($cacheKey);
        if (! is_string($esperado) || ! hash_equals($esperado, $codigoDigits)) {
            $falhas = (int) Cache::get($falhasKey, 0) + 1;
            Cache::put($falhasKey, $falhas, now()->addMinutes(self::OTP_TTL_MINUTES));

            return back()->withErrors(['codigo' => 'Código inválido ou expirado.']);
        }

        Cache::forget($cacheKey);
        Cache::forget($falhasKey);
        $request->session()->forget('fidelidade_otp_pending');

        $request->session()->put('fidelidade_acesso', [
            'empresa_id' => $empresa->id,
            'tel_norm' => $telNorm,
            'exp' => now()->addMinutes(self::ACESSO_TTL_MINUTES)->timestamp,
        ]);

        return redirect()
            ->route('publico.fidelidade', ['slug' => $slug])
            ->with('status', 'Telefone confirmado! Abaixo estão seus selos.');
    }

    public function cadastrar(Request $request, string $slug): RedirectResponse
    {
        $empresa = $this->empresaPorSlug($slug);
        $programa = $empresa->fidelidadePrograma;
        if (! $programa || ! $programa->ativo) {
            return redirect()
                ->route('publico.fidelidade', ['slug' => $slug])
                ->with('warning', 'O programa de fidelidade não está disponível nesta loja.');
        }

        $data = $request->validate([
            'cadastro_telefone' => ['required', 'string', 'min:8', 'max:32'],
            'cadastro_cpf' => ['required', 'string', 'max:18'],
            'cadastro_email' => ['required', 'email', 'max:255'],
        ]);

        $telNorm = FidelidadeCartao::normalizarTelefone($data['cadastro_telefone']);
        if (strlen($telNorm) < 8) {
            return back()
                ->withErrors(['cadastro_telefone' => 'Informe um telefone válido (DDD + número).'])
                ->withInput();
        }

        $cpfNorm = FidelidadeCartao::normalizarCpf($data['cadastro_cpf']);
        if ($cpfNorm === null || ! FidelidadeCartao::cpfValido($cpfNorm)) {
            return back()
                ->withErrors(['cadastro_cpf' => 'Informe um CPF válido.'])
                ->withInput();
        }

        $email = strtolower(trim($data['cadastro_email']));

        $conflito = FidelidadeCartao::conflitoCadastroFidelidade($empresa->id, $telNorm, $cpfNorm, $email, false);
        if ($conflito !== null) {
            return back()
                ->withErrors([$conflito['field'] => $conflito['message']])
                ->withInput();
        }

        $clienteId = null;
        foreach (Cliente::query()
            ->where('empresa_id', $empresa->id)
            ->whereNotNull('telefone')
            ->get(['id', 'telefone']) as $c) {
            if (FidelidadeCartao::normalizarTelefone($c->telefone) === $telNorm) {
                $clienteId = (int) $c->id;
                break;
            }
        }

        $create = [
            'cliente_id' => $clienteId,
            'selos' => 0,
            'total_resgates' => 0,
        ];
        if (Schema::hasColumn('fidelidade_cartoes', 'cpf_normalizado')) {
            $create['cpf_normalizado'] = $cpfNorm;
        }
        if (Schema::hasColumn('fidelidade_cartoes', 'email')) {
            $create['email'] = $email;
        }

        $cartao = FidelidadeCartao::query()->firstOrCreate(
            [
                'empresa_id' => $empresa->id,
                'telefone_normalizado' => $telNorm,
            ],
            $create
        );

        $atualizar = [];
        if (Schema::hasColumn('fidelidade_cartoes', 'cpf_normalizado')) {
            $atualizar['cpf_normalizado'] = $cpfNorm;
        }
        if (Schema::hasColumn('fidelidade_cartoes', 'email')) {
            $atualizar['email'] = $email;
        }
        if ($clienteId) {
            $atualizar['cliente_id'] = $clienteId;
        }
        if ($atualizar !== []) {
            $cartao->update($atualizar);
        }

        $request->session()->put('fidelidade_acesso', [
            'empresa_id' => $empresa->id,
            'tel_norm' => $telNorm,
            'exp' => now()->addMinutes(self::ACESSO_TTL_MINUTES)->timestamp,
        ]);
        $request->session()->forget('fidelidade_otp_pending');

        return redirect()
            ->route('publico.fidelidade', ['slug' => $slug])
            ->with('status', 'Cartão cadastrado com sucesso! Abaixo você já vê os selos deste telefone.');
    }

    private function fidelidadeAcessoValido(int $empresaId): ?array
    {
        $v = session('fidelidade_acesso');
        if (! is_array($v) || (int) ($v['empresa_id'] ?? 0) !== $empresaId) {
            return null;
        }
        if (time() > (int) ($v['exp'] ?? 0)) {
            session()->forget('fidelidade_acesso');

            return null;
        }

        if (! is_string($v['tel_norm'] ?? null) || strlen($v['tel_norm']) < 8) {
            return null;
        }

        return $v;
    }

    /**
     * Só considera OTP pendente se for desta loja e ainda existir código no cache (evita tela “código” sem campo de telefone após expirar ou trocar de loja).
     */
    private function otpPendenteValidoParaEmpresa(int $empresaId): bool
    {
        $pending = session('fidelidade_otp_pending');
        if (! is_array($pending) || (int) ($pending['empresa_id'] ?? 0) !== $empresaId) {
            return false;
        }
        $telNorm = $pending['tel_norm'] ?? null;
        if (! is_string($telNorm) || strlen($telNorm) < 8) {
            session()->forget('fidelidade_otp_pending');

            return false;
        }
        $codigo = Cache::get($this->cacheKeyOtp($empresaId, $telNorm));
        if (! is_string($codigo) || strlen($codigo) !== 6) {
            session()->forget('fidelidade_otp_pending');

            return false;
        }

        return true;
    }

    private function cacheKeyOtp(int $empresaId, string $telNorm): string
    {
        return 'fidelidade_otp_codigo:'.$empresaId.':'.$telNorm;
    }

    private function cacheKeyFalhas(int $empresaId, string $telNorm): string
    {
        return 'fidelidade_otp_falhas:'.$empresaId.':'.$telNorm;
    }

    /**
     * @param  array{resultado?: string}  $envio
     */
    private function mensagemFalhaEntrega(array $envio): string
    {
        return match ($envio['resultado'] ?? '') {
            FidelidadeOtpEntrega::FALHA_EMAIL => 'Não foi possível enviar o e-mail com o código. Verifique o envio de e-mails do site (MAIL_*) ou tente mais tarde.',
            FidelidadeOtpEntrega::FALHA_WHATSAPP => 'Não foi possível enviar o código pelo WhatsApp. Confira se a integração (Evolution) está ativa no servidor e as variáveis FIDELIDADE_OTP_* no .env; depois tente de novo ou fale com a loja.',
            FidelidadeOtpEntrega::FALHA_SEM_DESTINO => 'Não foi possível enviar o código pelo WhatsApp e não há e-mail válido no cartão. Atualize o cadastro acima ou fale com a loja.',
            default => 'Não foi possível enviar o código pelo WhatsApp. Tente mais tarde ou fale com a loja.',
        };
    }

    private function mensagemSucessoCodigoEnviado(string $canal, bool $reenvio): string
    {
        if ($canal === FidelidadeOtpEntrega::CANAL_EMAIL) {
            return $reenvio
                ? 'Enviamos um novo código para o e-mail cadastrado no seu cartão (confira caixa de entrada e spam). Digite-o abaixo para ver seus selos.'
                : 'Enviamos o código de 6 dígitos para o e-mail cadastrado no seu cartão (confira caixa de entrada e spam). Digite-o abaixo para ver seus selos.';
        }

        return $reenvio
            ? 'Enviamos um novo código no WhatsApp deste número. Digite-o para ver seus selos.'
            : 'Enviamos o código de 6 dígitos no WhatsApp deste número. Digite-o abaixo para ver seus selos.';
    }

    private function empresaPorSlug(string $slug): Empresa
    {
        $empresa = Empresa::query()
            ->where('slug', $slug)
            ->where('status', '!=', 'suspensa')
            ->with('fidelidadePrograma')
            ->first();

        if (! $empresa) {
            abort(404, 'Não encontramos esta loja. Verifique o link ou se o estabelecimento ainda está ativo.');
        }

        return $empresa;
    }
}
