<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FidelidadeCartao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FidelidadePublicController extends Controller
{
    public function show(string $slug): View
    {
        $empresa = $this->empresaPorSlug($slug);
        $programa = $empresa->fidelidadePrograma;

        $telefonePosCadastro = session('fidelidade_pos_cadastro');
        $ocultarCadastro = $programa && $programa->ativo
            && $telefonePosCadastro !== null
            && is_string($telefonePosCadastro)
            && trim($telefonePosCadastro) !== '';

        $cartao = null;
        $telefone_digitado = null;
        if ($ocultarCadastro) {
            $telefone_digitado = $telefonePosCadastro;
            if ($programa && $programa->ativo) {
                $norm = FidelidadeCartao::normalizarTelefone($telefone_digitado);
                if (strlen($norm) >= 8) {
                    $cartao = FidelidadeCartao::query()
                        ->where('empresa_id', $empresa->id)
                        ->where('telefone_normalizado', $norm)
                        ->first();
                }
            }
        }

        return view('publico.fidelidade', [
            'slug' => $slug,
            'empresa' => $empresa,
            'programa' => $programa,
            'cartao' => $cartao,
            'telefone_digitado' => $telefone_digitado,
            'ocultarCadastro' => $ocultarCadastro,
        ]);
    }

    public function consultar(Request $request, string $slug): View|RedirectResponse
    {
        $empresa = $this->empresaPorSlug($slug);

        $data = $request->validate([
            'telefone' => ['required', 'string', 'min:8', 'max:32'],
        ]);

        $norm = FidelidadeCartao::normalizarTelefone($data['telefone']);
        if (strlen($norm) < 8) {
            return back()->withErrors(['telefone' => 'Informe um telefone válido.'])->withInput();
        }

        $programa = $empresa->fidelidadePrograma;
        $cartao = null;
        if ($programa && $programa->ativo) {
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
            'telefone_digitado' => $data['telefone'],
            'ocultarCadastro' => false,
        ]);
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

        $existente = FidelidadeCartao::query()
            ->where('empresa_id', $empresa->id)
            ->where('telefone_normalizado', $telNorm)
            ->first();

        if (
            Schema::hasColumn('fidelidade_cartoes', 'cpf_normalizado')
            && $existente
            && $existente->cpf_normalizado
            && $existente->cpf_normalizado !== $cpfNorm
        ) {
            return back()
                ->withErrors(['cadastro_telefone' => 'Este telefone já está cadastrado com outro CPF.'])
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

        return redirect()
            ->route('publico.fidelidade', ['slug' => $slug])
            ->with('status', 'Cartão cadastrado com sucesso! Abaixo você já vê os selos deste telefone.')
            ->with('fidelidade_pos_cadastro', $data['cadastro_telefone']);
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
