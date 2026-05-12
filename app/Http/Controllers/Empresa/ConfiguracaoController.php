<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaSlug;
use App\Support\OsrmRouting;
use Illuminate\Database\QueryException;
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

        $categoriasBanner = collect();
        if (Empresa::schemaTemColunaLojaBannerCategoria()) {
            $categoriasBanner = Categoria::query()
                ->where('empresa_id', $empresa->id)
                ->where('ativo', true)
                ->orderBy('ordem')
                ->orderBy('nome')
                ->get();
        }

        $fretePreviewMapaOrigem = null;
        if (Schema::hasColumn('empresas', 'loja_frete_modo')
            && $empresa->lojaFreteModoEfetivo() === Empresa::LOJA_FRETE_OSRM_DISTANCIA) {
            $coords = $empresa->lojaEntregaCoordenadasOrigemSalvas();
            if ($coords !== null) {
                $fretePreviewMapaOrigem = ['lat' => $coords['lat'], 'lon' => $coords['lon']];
            } else {
                $addr = $empresa->lojaFreteOrigemEnderecoEfetiva();
                if ($addr !== null) {
                    $fretePreviewMapaOrigem = OsrmRouting::geocodeEndereco($addr);
                }
            }
        }

        return view('empresa.configuracoes.index', compact('empresa', 'categoriasBanner', 'fretePreviewMapaOrigem'));
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

        $rules['instagram_url'] = ['nullable', 'string', 'max:500'];
        $rules['facebook_url'] = ['nullable', 'string', 'max:500'];

        if (Schema::hasColumn('empresas', 'loja_filial_nome')) {
            $rules['loja_filial_nome'] = ['nullable', 'string', 'max:120'];
            $rules['loja_filial_link_url'] = ['nullable', 'string', 'max:500'];
            $rules['loja_filial_logo'] = ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
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
            $modoFreteInput = (string) $request->input('loja_frete_modo', '');
            $rules['loja_frete_google_rs_por_km'] = $modoFreteInput === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA
                ? ['required', 'numeric', 'min:0.01', 'max:99999999.99']
                : ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        }
        if (Schema::hasColumn('empresas', 'loja_entrega_lat_origem')) {
            $rules['loja_entrega_lat_origem'] = ['nullable', 'numeric', 'between:-90,90'];
            $rules['loja_entrega_lng_origem'] = ['nullable', 'numeric', 'between:-180,180'];
        }
        if (Schema::hasColumn('empresas', 'loja_entrega_km_incluso')) {
            $rules['loja_entrega_km_incluso'] = ['nullable', 'numeric', 'min:0.1', 'max:9999'];
            $rules['loja_entrega_valor_km_extra'] = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
            $rules['loja_entrega_gratis_acima_pedido'] = ['nullable', 'numeric', 'min:0', 'max:99999999.99'];
        }
        if (Schema::hasColumn('empresas', 'loja_entrega_chuva_ligado')) {
            $rules['loja_entrega_chuva_ligado'] = ['nullable', 'in:0,1'];
            $rules['loja_entrega_chuva_percentual'] = ['nullable', 'numeric', 'min:0', 'max:100'];
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
        if (Schema::hasColumn('empresas', 'loja_aberta')) {
            $rules['loja_aberta'] = ['required', 'in:0,1'];
        }
        if (Schema::hasColumn('empresas', 'loja_confirmar_pedidos')) {
            $rules['loja_confirmar_pedidos'] = ['nullable', 'in:0,1'];
        }
        if (Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada')) {
            $rules['loja_impressao_pedido_habilitada'] = ['nullable', 'in:0,1'];
        }

        if (Empresa::schemaTemColunaLojaBannerCategoria()) {
            $rules['loja_banner_categoria_id'] = [
                'nullable',
                'integer',
                Rule::exists('categorias', 'id')->where(fn ($q) => $q->where('empresa_id', $empresa->id)),
            ];
        }

        if (Empresa::schemaTemColunaLojaBannerCategoria() && $request->has('loja_banner_categoria_id')) {
            $rawBannerCat = $request->input('loja_banner_categoria_id');
            $request->merge([
                'loja_banner_categoria_id' => ($rawBannerCat === '' || $rawBannerCat === null) ? null : (int) $rawBannerCat,
            ]);
        }

        $data = $request->validate($rules);

        // Sempre gravar aqui: em vários hosts Schema::hasColumn('empresas', instagram_url)
        // retorna false mesmo com a coluna criada (information_schema / permissões).
        // O antigo else unset() fazia o salvamento nunca acontecer → vitrine sem ícones.
        $data['instagram_url'] = $this->normalizarUrlOpcional($request->input('instagram_url'), 'instagram_url');
        $data['facebook_url'] = $this->normalizarUrlOpcional($request->input('facebook_url'), 'facebook_url');

        if (Schema::hasColumn('empresas', 'loja_aberta')) {
            $data['loja_aberta'] = ((string) $request->input('loja_aberta')) === '1';
        } else {
            unset($data['loja_aberta']);
        }

        $podePedidosVitrineCfg = $empresa->temTelaMenu('loja_online') || $empresa->temTelaMenu('pedidos');

        if (Schema::hasColumn('empresas', 'loja_confirmar_pedidos')) {
            if ($podePedidosVitrineCfg && array_key_exists('loja_confirmar_pedidos', $request->all())) {
                $data['loja_confirmar_pedidos'] = (string) $request->input('loja_confirmar_pedidos') === '1';
            } else {
                unset($data['loja_confirmar_pedidos']);
            }
        } else {
            unset($data['loja_confirmar_pedidos']);
        }
        if (Schema::hasColumn('empresas', 'loja_impressao_pedido_habilitada')) {
            if ($podePedidosVitrineCfg && array_key_exists('loja_impressao_pedido_habilitada', $request->all())) {
                $data['loja_impressao_pedido_habilitada'] = (string) $request->input('loja_impressao_pedido_habilitada') === '1';
            } else {
                unset($data['loja_impressao_pedido_habilitada']);
            }
        } else {
            unset($data['loja_impressao_pedido_habilitada']);
        }

        if (Schema::hasColumn('empresas', 'cep')) {
            // Só altera o CEP no banco se o campo veio no POST (evita apagar ao salvar
            // sem o input no HTML, aba antiga em cache, etc.). String vazia = limpar CEP.
            if (array_key_exists('cep', $request->all())) {
                $digits = preg_replace('/\D+/', '', (string) $request->input('cep', ''));
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
        } else {
            unset($data['cep']);
        }

        $modoFrete = $data['loja_frete_modo'] ?? null;
        if (in_array($modoFrete, [Empresa::LOJA_FRETE_GOOGLE_DISTANCIA, Empresa::LOJA_FRETE_OSRM_DISTANCIA], true)
            && Schema::hasColumn('empresas', 'loja_frete_google_rs_por_km')) {
            if ($modoFrete === Empresa::LOJA_FRETE_GOOGLE_DISTANCIA && ! filled(config('services.google_maps.api_key'))) {
                throw ValidationException::withMessages([
                    'loja_frete_modo' => 'O servidor ainda não tem GOOGLE_MAPS_API_KEY no .env. Configure a chave e a Distance Matrix API no Google Cloud antes de usar este modo.',
                ]);
            }

            $origemCampo = trim((string) ($data['loja_frete_origem_endereco'] ?? ''));
            $origemEmpresa = trim((string) ($data['endereco'] ?? ''));
            $origemGlobal = trim((string) config('services.google_maps.default_origin_address', ''));
            $cepPersistidoOuNovo = Schema::hasColumn('empresas', 'cep')
                ? (array_key_exists('cep', $request->all())
                    ? ($data['cep'] ?? null)
                    : $empresa->cep)
                : null;
            $temCep = Schema::hasColumn('empresas', 'cep')
                && $cepPersistidoOuNovo !== null
                && trim((string) $cepPersistidoOuNovo) !== '';
            $latOrig = Schema::hasColumn('empresas', 'loja_entrega_lat_origem')
                ? ($data['loja_entrega_lat_origem'] ?? null)
                : null;
            $lngOrig = Schema::hasColumn('empresas', 'loja_entrega_lng_origem')
                ? ($data['loja_entrega_lng_origem'] ?? null)
                : null;
            $temCoordSalva = $latOrig !== null && $latOrig !== ''
                && $lngOrig !== null && $lngOrig !== '';
            if ($origemCampo === '' && $origemEmpresa === '' && $origemGlobal === '' && ! $temCep && ! $temCoordSalva) {
                $msg = $modoFrete === Empresa::LOJA_FRETE_OSRM_DISTANCIA
                    ? 'Para frete por rota: informe latitude/longitude de origem, ou CEP/endereço da loja, ou endereço em Saída das entregas.'
                    : 'Informe o CEP da loja, o endereço de origem do frete, o Endereço em Dados da empresa, ou defina GOOGLE_MAPS_DEFAULT_ORIGIN_ADDRESS no servidor.';
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
        if (Schema::hasColumn('empresas', 'loja_entrega_lat_origem')) {
            foreach (['loja_entrega_lat_origem', 'loja_entrega_lng_origem'] as $c) {
                $v = $data[$c] ?? null;
                $data[$c] = ($v === null || $v === '') ? null : round((float) $v, 7);
            }
            foreach (['loja_entrega_km_incluso', 'loja_entrega_valor_km_extra'] as $c) {
                $v = $data[$c] ?? null;
                $data[$c] = ($v === null || $v === '') ? null : round((float) $v, 2);
            }
            $vg = $data['loja_entrega_gratis_acima_pedido'] ?? null;
            $data['loja_entrega_gratis_acima_pedido'] = ($vg === null || $vg === '') ? null : round((float) $vg, 2);
        } else {
            unset(
                $data['loja_entrega_lat_origem'],
                $data['loja_entrega_lng_origem'],
                $data['loja_entrega_km_incluso'],
                $data['loja_entrega_valor_km_extra'],
                $data['loja_entrega_gratis_acima_pedido']
            );
        }
        if (Schema::hasColumn('empresas', 'loja_entrega_chuva_ligado')) {
            $data['loja_entrega_chuva_ligado'] = $request->boolean('loja_entrega_chuva_ligado');
            $vp = $data['loja_entrega_chuva_percentual'] ?? null;
            $data['loja_entrega_chuva_percentual'] = ($vp === null || $vp === '') ? null : round((float) $vp, 2);
        } else {
            unset($data['loja_entrega_chuva_ligado'], $data['loja_entrega_chuva_percentual']);
        }

        if (Empresa::schemaTemColunaLojaBannerCategoria()) {
            if (! $empresa->temTelaMenu('loja_online') || ! $request->has('loja_banner_categoria_id')) {
                unset($data['loja_banner_categoria_id']);
            } else {
                $raw = $data['loja_banner_categoria_id'] ?? null;
                $data['loja_banner_categoria_id'] = ($raw === null || $raw === '') ? null : (int) $raw;
            }
        }

        if (! Empresa::schemaTemColunaLojaBannerCategoria()) {
            unset($data['loja_banner_categoria_id']);
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

        if (Schema::hasColumn('empresas', 'loja_filial_nome')) {
            $nomeF = trim((string) ($data['loja_filial_nome'] ?? ''));
            if ($nomeF === '') {
                $data['loja_filial_nome'] = null;
                $data['loja_filial_link_url'] = null;
                if ($empresa->loja_filial_logo) {
                    $this->removerLogoFilialDoDisco($empresa);
                }
                $data['loja_filial_logo'] = null;
            } else {
                $data['loja_filial_nome'] = $nomeF;
                $data['loja_filial_link_url'] = $this->normalizarUrlOpcional(
                    $request->input('loja_filial_link_url'),
                    'loja_filial_link_url'
                );
                $filialLogo = $request->file('loja_filial_logo');
                if ($filialLogo instanceof UploadedFile) {
                    $this->removerLogoFilialDoDisco($empresa);
                    $data['loja_filial_logo'] = $this->armazenarLogoFilial($filialLogo, $empresa);
                } else {
                    unset($data['loja_filial_logo']);
                }
            }
        } else {
            unset($data['loja_filial_nome'], $data['loja_filial_link_url'], $data['loja_filial_logo']);
        }

        $logo = $request->file('logo');
        if ($logo instanceof UploadedFile) {
            $data['logo'] = $this->armazenarLogo($logo, $empresa);
            $this->removerLogoAnteriorDoDisco($empresa);
        }

        try {
            $empresa->update($data);
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            $code = $e->errorInfo[1] ?? null;
            $unknownColumn = $code === 1054
                || str_contains($msg, 'Unknown column')
                || str_contains($msg, 'does not exist');

            if (
                $unknownColumn
                && (
                    str_contains($msg, 'instagram_url')
                    || str_contains($msg, 'facebook_url')
                    || str_contains($msg, 'loja_aberta')
                    || str_contains($msg, 'loja_banner_categoria_id')
                    || str_contains($msg, 'loja_filial')
                )
            ) {
                unset(
                    $data['instagram_url'],
                    $data['facebook_url'],
                    $data['loja_aberta'],
                    $data['loja_banner_categoria_id'],
                    $data['loja_filial_nome'],
                    $data['loja_filial_logo'],
                    $data['loja_filial_link_url']
                );
                $empresa->update($data);

                return redirect()
                    ->route('empresa.configuracoes.index')
                    ->with(
                        'warning',
                        'Os demais dados foram salvos. Rode php artisan migrate no servidor para criar colunas novas (Instagram/Facebook/status da loja/banner/filial no topo) e salve de novo.'
                    );
            }

            throw $e;
        }

        return redirect()
            ->route('empresa.configuracoes.index')
            ->with('status', 'Configurações salvas.');
    }

    private function normalizarUrlOpcional(?string $valor, string $campo): ?string
    {
        $v = $valor !== null ? trim($valor) : '';
        if ($v === '') {
            return null;
        }
        if ($campo === 'instagram_url' && str_starts_with($v, '@')) {
            $v = 'https://instagram.com/'.ltrim($v, '@');
        }
        if (! preg_match('#^https?://#i', $v)) {
            $v = 'https://'.ltrim($v, '/');
        }
        if (! filter_var($v, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                $campo => 'Informe uma URL válida (ex.: https://instagram.com/sua_loja).',
            ]);
        }

        return $v;
    }

    private function armazenarLogo(UploadedFile $file, Empresa $empresa): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'png';
        $nome = Str::uuid()->toString().'.'.$ext;
        $dir = 'empresas/'.$empresa->id;

        return $file->storeAs($dir, $nome, 'uploads');
    }

    private function armazenarLogoFilial(UploadedFile $file, Empresa $empresa): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'png';
        $nome = Str::uuid()->toString().'.'.$ext;
        $dir = 'empresas/'.$empresa->id.'/filial';

        return $file->storeAs($dir, $nome, 'uploads');
    }

    private function removerLogoFilialDoDisco(Empresa $empresa): void
    {
        if (! $empresa->loja_filial_logo) {
            return;
        }

        $path = ltrim(str_replace('\\', '/', (string) $empresa->loja_filial_logo), '/');

        if (Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);

            return;
        }

        Storage::disk('public')->delete($empresa->loja_filial_logo);
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
