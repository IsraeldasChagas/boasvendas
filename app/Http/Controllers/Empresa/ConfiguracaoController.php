<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $rules = [
            'nome' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('empresas', 'slug')->ignore($empresa->id),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'email_contato' => ['nullable', 'email', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:32'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
        ];

        if (Schema::hasColumn('empresas', 'cep')) {
            $rules['cep'] = ['nullable', 'string', 'max:16'];
        }

        $rules = array_merge($rules, [
            'loja_pix_instrucoes' => ['nullable', 'string', 'max:4000'],
            'loja_pix_chave_tipo' => ['nullable', 'string', Rule::in(array_keys(Empresa::pixChaveTiposRotulos()))],
            'loja_pix_chave_valor' => ['nullable', 'string', 'max:255'],
            'loja_pix_banco' => ['nullable', 'string', 'max:120'],
            'loja_pix_copia_cola' => ['nullable', 'string', 'max:8192'],
        ]);

        if (Schema::hasColumn('empresas', 'loja_taxa_entrega_padrao')) {
            $rules['loja_taxa_entrega_padrao'] = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        }
        if (Schema::hasColumn('empresas', 'loja_permite_retirada_balcao')) {
            $rules['loja_permite_retirada_balcao'] = ['nullable', 'in:0,1'];
        }
        if (Schema::hasColumn('empresas', 'loja_frete_modo')) {
            $rules['loja_frete_modo'] = ['required', 'string', Rule::in(array_keys(Empresa::lojaFreteModosRotulos()))];
        }
        if (Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            $rules['loja_frete_google_rs_por_km'] = $request->input('loja_frete_modo') === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA
                ? ['required', 'numeric', 'min:0.01', 'max:99999999.99']
                : ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        }
        if (Schema::hasColumn('empresas', 'loja_frete_google_taxa_minima')) {
            $rules['loja_frete_google_taxa_minima'] = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        }
        if (Schema::hasColumn('empresas', 'loja_frete_google_km_max')) {
            $rules['loja_frete_google_km_max'] = ['nullable', 'numeric', 'min:0', 'max:9999'];
        }
        if (Schema::hasColumn('empresas', 'loja_frete_origem_endereco')) {
            $rules['loja_frete_origem_endereco'] = ['nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);

        if (Schema::hasColumn('empresas', 'cep')) {
            $digits = preg_replace('/\D+/', '', (string) ($data['cep'] ?? ''));
            if ($digits === '') {
                $data['cep'] = null;
            } elseif (strlen($digits) === 8) {
                $data['cep'] = $digits;
            } else {
                throw ValidationException::withMessages([
                    'cep' => 'Informe o CEP com 8 dígitos ou deixe em branco.',
                ]);
            }
        } else {
            unset($data['cep']);
        }

        if (($data['loja_frete_modo'] ?? null) === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA
            && Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            if (! filled(config('services.google_maps.api_key'))) {
                throw ValidationException::withMessages([
                    'loja_frete_modo' => 'O servidor ainda não tem GOOGLE_MAPS_API_KEY no .env. Configure a chave e a Distance Matrix API no Google Cloud antes de usar este modo.',
                ]);
            }

            $origemCampo = trim((string) ($data['loja_frete_origem_endereco'] ?? ''));
            $origemEmpresa = trim((string) ($data['endereco'] ?? ''));
            $origemGlobal = trim((string) config('services.google_maps.default_origin_address', ''));
            $temCep = Schema::hasColumn('empresas', 'cep') && ($data['cep'] ?? null) !== null;
            if ($origemCampo === '' && $origemEmpresa === '' && $origemGlobal === '' && ! $temCep) {
                $msg = 'Informe o CEP da loja, o endereço de origem do frete, o Endereço em Dados da empresa, ou defina GOOGLE_MAPS_DEFAULT_ORIGIN_ADDRESS no servidor.';
                if (Schema::hasColumn('empresas', 'loja_frete_origem_endereco')) {
                    throw ValidationException::withMessages(['loja_frete_origem_endereco' => $msg]);
                }
                throw ValidationException::withMessages(['endereco' => $msg]);
            }
        }

        // Evita quebrar a vitrine ao salvar sem slug: se a empresa já tem slug,
        // não permitimos que ele vire null por acidente ao editar outras infos.
        if (! isset($data['slug']) || $data['slug'] === null || $data['slug'] === '') {
            unset($data['slug']);
        }

        if (Schema::hasColumn('empresas', 'loja_taxa_entrega_padrao')) {
            $v = $data['loja_taxa_entrega_padrao'] ?? null;
            $data['loja_taxa_entrega_padrao'] = ($v === null || $v === '') ? null : round((float) $v, 2);
        }
        if (Schema::hasColumn('empresas', 'loja_permite_retirada_balcao') && $request->has('loja_permite_retirada_balcao')) {
            $data['loja_permite_retirada_balcao'] = (string) $request->input('loja_permite_retirada_balcao') === '1';
        }
        if (Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            $v = $data['loja_frete_google_rs_por_km'] ?? null;
            $data['loja_frete_google_rs_por_km'] = ($v === null || $v === '') ? null : round((float) $v, 2);
        }
        if (Schema::hasColumn('empresas', 'loja_frete_google_taxa_minima')) {
            $v = $data['loja_frete_google_taxa_minima'] ?? null;
            $data['loja_frete_google_taxa_minima'] = ($v === null || $v === '') ? null : round((float) $v, 2);
        }
        if (Schema::hasColumn('empresas', 'loja_frete_google_km_max')) {
            $v = $data['loja_frete_google_km_max'] ?? null;
            $data['loja_frete_google_km_max'] = ($v === null || $v === '') ? null : round((float) $v, 2);
        }

        $slugAnterior = (string) ($empresa->slug ?? '');
        $slugNovo = (string) ($data['slug'] ?? $empresa->slug ?? '');
        $mudouSlug = $slugAnterior !== '' && $slugNovo !== '' && $slugAnterior !== $slugNovo;
        if ($mudouSlug) {
            EmpresaSlug::query()->firstOrCreate([
                'slug' => $slugAnterior,
            ], [
                'empresa_id' => $empresa->id,
            ]);
        }

        $logo = $request->file('logo');
        if ($logo instanceof UploadedFile) {
            $data['logo'] = $this->armazenarLogo($logo, $empresa);
            $this->removerLogoAnteriorDoDisco($empresa);
        }

        $empresa->update($data);

        return redirect()
            ->route('empresa.configuracoes.index')
            ->with('status', 'Configurações salvas.');
    }

    private function armazenarLogo(UploadedFile $file, Empresa $empresa): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'png';
        $nome = Str::uuid()->toString().'.'.$ext;
        $dir = 'empresas/'.$empresa->id;

        return $file->storeAs($dir, $nome, 'uploads');
    }

    private function removerLogoAnteriorDoDisco(Empresa $empresa): void
    {
        if (! $empresa->logo) {
            return;
        }

        $path = ltrim(str_replace('\\', '/', $empresa->logo), '/');

        if (Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);

            return;
        }

        Storage::disk('public')->delete($empresa->logo);
    }
}
