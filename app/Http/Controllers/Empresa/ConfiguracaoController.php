<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para acessar as configurações.');
        }

        $empresa->load('plano');

        return view('empresa.configuracoes.index', compact('empresa'));
    }

    public function update(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para alterar as configurações.');
        }

        $rawSlug = $request->input('slug');
        $slugNormalizado = is_string($rawSlug) && trim($rawSlug) !== ''
            ? strtolower(trim($rawSlug))
            : null;
        $request->merge(['slug' => $slugNormalizado]);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('empresas', 'slug')->ignore($empresa->id),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'email_contato' => ['nullable', 'email', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:32'],
            'loja_pix_instrucoes' => ['nullable', 'string', 'max:4000'],
            'loja_pix_chave_tipo' => ['nullable', 'string', Rule::in(array_keys(Empresa::pixChaveTiposRotulos()))],
            'loja_pix_chave_valor' => ['nullable', 'string', 'max:255'],
            'loja_pix_banco' => ['nullable', 'string', 'max:120'],
            'loja_pix_copia_cola' => ['nullable', 'string', 'max:8192'],
        ]);

        $slugAnterior = (string) ($empresa->slug ?? '');
        $slugNovo = (string) ($data['slug'] ?? '');
        $mudouSlug = $slugAnterior !== '' && $slugNovo !== '' && $slugAnterior !== $slugNovo;
        if ($mudouSlug) {
            EmpresaSlug::query()->firstOrCreate([
                'slug' => $slugAnterior,
            ], [
                'empresa_id' => $empresa->id,
            ]);
        }

        $empresa->update($data);

        return redirect()
            ->route('empresa.configuracoes.index')
            ->with('status', 'Configurações salvas.');
    }
}
