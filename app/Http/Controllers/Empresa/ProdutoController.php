<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\ProdutoIngrediente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar produtos.');
        }

        $query = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->with('categoria')
            ->orderBy('nome');

        if ($request->filled('q')) {
            $q = $request->string('q')->trim();
            $query->where(function ($sub) use ($q) {
                $sub->where('nome', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhereHas('categoria', fn ($c) => $c->where('nome', 'like', '%'.$q.'%'));
            });
        }

        if ($request->filled('ativo')) {
            $ativo = $request->input('ativo');
            if ($ativo === '1') {
                $query->where('ativo', true);
            }
            if ($ativo === '0') {
                $query->where('ativo', false);
            }
        }

        $produtos = $query->get();

        return view('empresa.produtos.index', compact('empresa', 'produtos'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para cadastrar produtos.');
        }

        $categorias = Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        $adicionais = Adicional::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('empresa.produtos.create', compact('empresa', 'categorias', 'adicionais'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request, $empresa);
        $itensIng = $this->coletaIngredientesDoRequest($request, $empresa);
        $this->validarLimiteRetirarIngredientes($request, $itensIng);
        $data['max_ingredientes_retirar'] = count($itensIng) > 0 ? (int) $request->input('max_ingredientes_retirar') : null;
        $this->aplicarIngredientesRetirarUi($request, $data, $itensIng, null);
        $data['empresa_id'] = $empresa->id;
        $data['sku'] = $this->gerarCodigoInternoUnico($empresa);

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->armazenarFoto($request->file('foto'), $empresa);
        }

        $produto = Produto::query()->create($data);
        $this->syncIngredientesDoProduto($produto, $itensIng, $empresa);
        $this->syncAdicionaisDoProduto($produto, $empresa, $request);

        return redirect()
            ->route('empresa.produtos.index')
            ->with('status', 'Produto cadastrado.');
    }

    public function edit(Request $request, Produto $produto): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $produto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $categorias = Categoria::query()
            ->where('empresa_id', $empresa->id)
            ->where(function ($q) use ($produto) {
                $q->where('ativo', true);
                if ($produto->categoria_id) {
                    $q->orWhere('id', $produto->categoria_id);
                }
            })
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        $adicionais = Adicional::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        $produto->load(['adicionais', 'ingredientes']);

        return view('empresa.produtos.edit', compact('empresa', 'produto', 'categorias', 'adicionais'));
    }

    public function update(Request $request, Produto $produto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $produto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $data = $this->validated($request, $empresa, $produto);
        $itensIng = $this->coletaIngredientesDoRequest($request, $empresa);
        $this->validarLimiteRetirarIngredientes($request, $itensIng, $produto);
        if (count($itensIng) > 0) {
            $data['max_ingredientes_retirar'] = array_key_exists('max_ingredientes_retirar', $request->all())
                ? max(0, min(255, (int) $request->input('max_ingredientes_retirar')))
                : $produto->max_ingredientes_retirar;
        } else {
            $data['max_ingredientes_retirar'] = null;
        }
        $this->aplicarIngredientesRetirarUi($request, $data, $itensIng, $produto);

        if ($request->hasFile('foto')) {
            $this->removerFotoDoDisco($produto);
            $data['foto'] = $this->armazenarFoto($request->file('foto'), $empresa);
        }

        $produto->update($data);
        $this->syncIngredientesDoProduto($produto, $itensIng, $empresa);
        $this->syncAdicionaisDoProduto($produto, $empresa, $request);
        // Garante que o "Editar" reflita atualização mesmo quando só muda relacionamentos.
        $produto->touch();

        return redirect()
            ->route('empresa.produtos.index')
            ->with('status', 'Produto atualizado.');
    }

    public function destroy(Request $request, Produto $produto): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $produto->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $this->removerFotoDoDisco($produto);
        foreach ($produto->ingredientes()->whereNotNull('foto')->pluck('foto') as $pathIng) {
            $this->removerFotoIngredienteDoDisco(ltrim(str_replace('\\', '/', (string) $pathIng), '/'));
        }
        $produto->delete();

        return redirect()
            ->route('empresa.produtos.index')
            ->with('status', 'Produto removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Empresa $empresa, ?Produto $produto = null): array
    {
        $rules = [
            'nome' => ['required', 'string', 'max:255'],
            'categoria_id' => [
                'nullable',
                'integer',
                Rule::exists('categorias', 'id')->where(fn ($q) => $q->where('empresa_id', $empresa->id)),
            ],
            'preco' => ['required', 'numeric', 'min:0'],
            'estoque' => ['required', 'integer', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:10000'],
            'foto' => ['nullable', 'image', 'max:3072'],
            'visivel_loja' => ['sometimes', 'boolean'],
            'ativo' => ['sometimes', 'boolean'],
            'permite_adicionais' => ['sometimes', 'boolean'],
            'adicional_ids' => ['nullable', 'array'],
            'adicional_ids.*' => [
                'integer',
                Rule::exists('adicionais', 'id')->where(fn ($q) => $q->where('empresa_id', $empresa->id)->where('ativo', true)),
            ],
            'ingrediente_nomes' => ['nullable', 'array'],
            'ingrediente_nomes.*' => ['nullable', 'string', 'max:120'],
            'ingrediente_foto_atual' => ['nullable', 'array'],
            'ingrediente_foto_atual.*' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail) use ($empresa) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $v = ltrim(str_replace('\\', '/', (string) $value), '/');
                    if (Str::contains($v, '..')) {
                        $fail('Caminho de foto inválido.');

                        return;
                    }
                    $prefix = 'produtos/'.$empresa->id.'/ingredientes/';
                    if (! Str::startsWith($v, $prefix)) {
                        $fail('Foto de ingrediente inválida.');
                    }
                },
            ],
            'ingrediente_fotos' => ['nullable', 'array'],
            'ingrediente_fotos.*' => ['nullable', 'image', 'max:2048'],
        ];

        if (Schema::hasColumn('produtos', 'acrescimo_escolhas_min')) {
            $rules['acrescimo_escolhas_min'] = ['nullable', 'integer', 'min:0', 'max:999'];
            $rules['acrescimo_escolhas_max'] = ['nullable', 'integer', 'min:0', 'max:999'];
        }

        if (Schema::hasColumn('produtos', 'ingredientes_retirar_ui')) {
            $rules['ingredientes_retirar_ui'] = ['nullable', 'string', Rule::in([
                Produto::ING_RETIRAR_UI_STEPPER,
                Produto::ING_RETIRAR_UI_CHECKBOX,
            ])];
        }

        $data = $request->validate($rules);

        $data['visivel_loja'] = $request->boolean('visivel_loja');
        $data['ativo'] = $request->boolean('ativo');
        if ($produto === null || $request->has('permite_adicionais')) {
            $data['permite_adicionais'] = $request->boolean('permite_adicionais');
        } else {
            unset($data['permite_adicionais']);
        }

        if (Schema::hasColumn('produtos', 'acrescimo_escolhas_min')) {
            $hadMin = array_key_exists('acrescimo_escolhas_min', $data);
            $hadMax = array_key_exists('acrescimo_escolhas_max', $data);

            if ($hadMin) {
                $mn = $data['acrescimo_escolhas_min'];
                $data['acrescimo_escolhas_min'] = ($mn === null || $mn === '') ? null : (int) $mn;
            }
            if ($hadMax) {
                $mx = $data['acrescimo_escolhas_max'];
                $data['acrescimo_escolhas_max'] = ($mx === null || $mx === '') ? null : (int) $mx;
            }

            $mnCompare = $hadMin ? $data['acrescimo_escolhas_min'] : $produto?->acrescimo_escolhas_min;
            $mxCompare = $hadMax ? $data['acrescimo_escolhas_max'] : $produto?->acrescimo_escolhas_max;
            if ($mnCompare !== null && $mxCompare !== null && $mnCompare > $mxCompare) {
                throw ValidationException::withMessages([
                    'acrescimo_escolhas_max' => 'O máximo de escolhas deve ser maior ou igual ao mínimo.',
                ]);
            }

            $permiteAdicionaisEfetivo = array_key_exists('permite_adicionais', $data)
                ? (bool) $data['permite_adicionais']
                : (bool) ($produto?->permite_adicionais ?? false);
            if (! $permiteAdicionaisEfetivo) {
                $data['acrescimo_escolhas_min'] = null;
                $data['acrescimo_escolhas_max'] = null;
            }
        }

        unset($data['foto'], $data['ingrediente_nomes'], $data['ingrediente_foto_atual'], $data['ingrediente_fotos']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{nome: string, foto_atual: ?string, file: ?UploadedFile}>  $itensIng
     */
    private function aplicarIngredientesRetirarUi(Request $request, array &$data, array $itensIng, ?Produto $produto): void
    {
        if (! Schema::hasColumn('produtos', 'ingredientes_retirar_ui')) {
            return;
        }

        if ($itensIng === []) {
            $data['ingredientes_retirar_ui'] = null;

            return;
        }

        $v = $request->input('ingredientes_retirar_ui');
        if (in_array($v, [Produto::ING_RETIRAR_UI_STEPPER, Produto::ING_RETIRAR_UI_CHECKBOX], true)) {
            $data['ingredientes_retirar_ui'] = $v;

            return;
        }

        // Campo ausente no POST (formulário grande, max_input_vars, etc.): ao editar, não forçar stepper.
        $anterior = $produto?->ingredientes_retirar_ui;
        if ($anterior !== null && in_array($anterior, [Produto::ING_RETIRAR_UI_STEPPER, Produto::ING_RETIRAR_UI_CHECKBOX], true)) {
            $data['ingredientes_retirar_ui'] = $anterior;

            return;
        }

        $data['ingredientes_retirar_ui'] = Produto::ING_RETIRAR_UI_STEPPER;
    }

    /**
     * @return list<array{nome: string, foto_atual: ?string, file: ?UploadedFile}>
     */
    private function coletaIngredientesDoRequest(Request $request, Empresa $empresa): array
    {
        $nomes = $request->input('ingrediente_nomes', []);
        if (! is_array($nomes)) {
            $nomes = [];
        }
        $atuais = $request->input('ingrediente_foto_atual', []);
        if (! is_array($atuais)) {
            $atuais = [];
        }
        $files = $request->file('ingrediente_fotos', []);
        if (! is_array($files)) {
            $files = [];
        }

        $out = [];
        foreach ($nomes as $i => $n) {
            $nome = trim(strip_tags((string) $n));
            if ($nome === '') {
                continue;
            }

            $fotoAtualRaw = isset($atuais[$i]) ? trim((string) $atuais[$i]) : '';
            $fotoAtual = $this->fotoAtualIngredienteValidaParaEmpresa($fotoAtualRaw, $empresa);

            $file = null;
            if (isset($files[$i]) && $files[$i] instanceof UploadedFile && $files[$i]->isValid()) {
                $file = $files[$i];
            }

            $out[] = [
                'nome' => Str::limit($nome, 120, ''),
                'foto_atual' => $fotoAtual,
                'file' => $file,
            ];
        }

        return $out;
    }

    private function fotoAtualIngredienteValidaParaEmpresa(string $path, Empresa $empresa): ?string
    {
        if ($path === '') {
            return null;
        }
        $rel = ltrim(str_replace('\\', '/', $path), '/');
        if ($rel === '' || Str::contains($rel, '..')) {
            return null;
        }
        $prefix = 'produtos/'.$empresa->id.'/ingredientes/';
        if (! Str::startsWith($rel, $prefix)) {
            return null;
        }

        return $rel;
    }

    /**
     * @param  list<array{nome: string, foto_atual: ?string, file: ?UploadedFile}>  $ingredientes
     */
    private function validarLimiteRetirarIngredientes(Request $request, array $ingredientes, ?Produto $produto = null): void
    {
        if ($ingredientes === []) {
            return;
        }

        $payload = $request->all();
        if ($produto !== null && ! array_key_exists('max_ingredientes_retirar', $payload)) {
            $payload['max_ingredientes_retirar'] = $produto->max_ingredientes_retirar;
        }

        Validator::make($payload, [
            'max_ingredientes_retirar' => ['required', 'integer', 'min:0', 'max:'.count($ingredientes)],
        ], [
            'max_ingredientes_retirar.required' => 'Informe quantos ingredientes o cliente pode pedir para retirar (0 = nenhum).',
        ])->validate();
    }

    /**
     * @param  list<array{nome: string, foto_atual: ?string, file: ?UploadedFile}>  $items
     */
    private function syncIngredientesDoProduto(Produto $produto, array $items, Empresa $empresa): void
    {
        $oldPaths = $produto->ingredientes()
            ->whereNotNull('foto')
            ->pluck('foto')
            ->map(fn ($p) => ltrim(str_replace('\\', '/', (string) $p), '/'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $produto->ingredientes()->delete();

        $newPaths = [];
        foreach ($items as $i => $item) {
            $foto = null;
            if ($item['file'] !== null) {
                $foto = $this->armazenarFotoIngrediente($item['file'], $empresa);
            } elseif ($item['foto_atual'] !== null) {
                $foto = $item['foto_atual'];
            }

            if ($foto !== null) {
                $newPaths[] = ltrim(str_replace('\\', '/', $foto), '/');
            }

            ProdutoIngrediente::query()->create([
                'produto_id' => $produto->id,
                'nome' => $item['nome'],
                'foto' => $foto,
                'ordem' => $i,
            ]);
        }

        $newPaths = array_values(array_unique($newPaths));

        foreach ($oldPaths as $old) {
            if ($old !== '' && ! in_array($old, $newPaths, true)) {
                $this->removerFotoIngredienteDoDisco($old);
            }
        }
    }

    private function syncAdicionaisDoProduto(Produto $produto, Empresa $empresa, Request $request): void
    {
        if (! $produto->permite_adicionais) {
            $produto->adicionais()->detach();

            return;
        }

        // Formulário completo envia o marcador; sem ele (submit parcial/raro), não sobrescreve vínculos.
        if (! $request->has('adicional_catalogo_enviado')) {
            return;
        }

        $ids = collect($request->input('adicional_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $valid = Adicional::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->where('tipo', Adicional::TIPO_ACRESCENTAR)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $produto->adicionais()->sync($valid);
    }

    /** Garante SKU único por empresa (coluna `sku` no banco). */
    private function gerarCodigoInternoUnico(Empresa $empresa): string
    {
        do {
            $sku = 'CI-'.strtoupper(Str::random(8));
        } while (
            Produto::query()
                ->where('empresa_id', $empresa->id)
                ->where('sku', $sku)
                ->exists()
        );

        return $sku;
    }

    private function armazenarFoto(UploadedFile $file, Empresa $empresa): string
    {
        $nome = Str::uuid()->toString().'.jpg';
        $dir = 'produtos/'.$empresa->id;
        $path = $dir.'/'.$nome;

        // Compatibilidade mobile: reencoda como JPEG (evita WebP não suportado em alguns aparelhos).
        try {
            $img = @imagecreatefromstring($file->get());
            if ($img !== false) {
                $w = imagesx($img);
                $h = imagesy($img);

                // Reduz para um tamanho "ideal" (mais leve e bonito) mantendo proporção.
                // Não aumenta imagens pequenas.
                $max = 1400;
                $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));

                $dst = imagecreatetruecolor($nw, $nh);
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
                imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

                ob_start();
                imagejpeg($dst, null, 85);
                $jpeg = ob_get_clean();

                imagedestroy($dst);
                imagedestroy($img);

                if (is_string($jpeg) && $jpeg !== '') {
                    $disk = Storage::disk('uploads');
                    $disk->makeDirectory($dir);
                    if ($disk->put($path, $jpeg)) {
                        return $path;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback abaixo
        }

        // Fallback: salva original.
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'jpg';
        $fallbackNome = Str::uuid()->toString().'.'.$ext;
        $fallbackPath = $dir.'/'.$fallbackNome;
        $file->storeAs($dir, $fallbackNome, 'uploads');

        return $fallbackPath;
    }

    /**
     * Miniatura opcional do ingrediente (JPEG redimensionado, pasta própria).
     */
    private function armazenarFotoIngrediente(UploadedFile $file, Empresa $empresa): string
    {
        $nome = Str::uuid()->toString().'.jpg';
        $dir = 'produtos/'.$empresa->id.'/ingredientes';
        $path = $dir.'/'.$nome;

        try {
            $img = @imagecreatefromstring($file->get());
            if ($img !== false) {
                $w = imagesx($img);
                $h = imagesy($img);

                $max = 512;
                $scale = ($w > 0 && $h > 0) ? min(1, $max / max($w, $h)) : 1;
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));

                $dst = imagecreatetruecolor($nw, $nh);
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
                imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

                ob_start();
                imagejpeg($dst, null, 85);
                $jpeg = ob_get_clean();

                imagedestroy($dst);
                imagedestroy($img);

                if (is_string($jpeg) && $jpeg !== '') {
                    $disk = Storage::disk('uploads');
                    $disk->makeDirectory($dir);
                    if ($disk->put($path, $jpeg)) {
                        return $path;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback abaixo
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'jpg';
        $fallbackNome = Str::uuid()->toString().'.'.$ext;
        $fallbackPath = $dir.'/'.$fallbackNome;
        $file->storeAs($dir, $fallbackNome, 'uploads');

        return $fallbackPath;
    }

    private function removerFotoIngredienteDoDisco(string $pathRel): void
    {
        $path = ltrim(str_replace('\\', '/', $pathRel), '/');
        if ($path === '') {
            return;
        }

        if (Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);

            return;
        }

        Storage::disk('public')->delete($pathRel);
    }

    private function removerFotoDoDisco(Produto $produto): void
    {
        if (! $produto->foto) {
            return;
        }

        $path = ltrim(str_replace('\\', '/', $produto->foto), '/');

        if (Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);

            return;
        }

        Storage::disk('public')->delete($produto->foto);
    }
}
