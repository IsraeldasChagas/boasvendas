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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
        $nom = $this->listaIngredientesDoRequest($request);
        $this->validarLimiteRetirarIngredientes($request, $nom);
        $data['max_ingredientes_retirar'] = count($nom) > 0 ? (int) $request->input('max_ingredientes_retirar') : null;
        $data['empresa_id'] = $empresa->id;
        $data['sku'] = $this->gerarCodigoInternoUnico($empresa);

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->armazenarFoto($request->file('foto'), $empresa);
        }

        $produto = Produto::query()->create($data);
        $this->syncIngredientesDoProduto($produto, $nom);
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
        $nom = $this->listaIngredientesDoRequest($request);
        $this->validarLimiteRetirarIngredientes($request, $nom);
        $data['max_ingredientes_retirar'] = count($nom) > 0 ? (int) $request->input('max_ingredientes_retirar') : null;

        if ($request->hasFile('foto')) {
            $this->removerFotoDoDisco($produto);
            $data['foto'] = $this->armazenarFoto($request->file('foto'), $empresa);
        }

        $produto->update($data);
        $this->syncIngredientesDoProduto($produto, $nom);
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
        $data = $request->validate([
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
        ]);

        $data['visivel_loja'] = $request->boolean('visivel_loja');
        $data['ativo'] = $request->boolean('ativo');
        $data['permite_adicionais'] = $request->boolean('permite_adicionais');

        unset($data['foto'], $data['ingrediente_nomes']);

        return $data;
    }

    /**
     * @return list<string>
     */
    private function listaIngredientesDoRequest(Request $request): array
    {
        return collect($request->input('ingrediente_nomes', []))
            ->map(function ($n) {
                $t = trim(strip_tags((string) $n));

                return $t !== '' ? Str::limit($t, 120, '') : '';
            })
            ->filter(fn (string $n) => $n !== '')
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $ingredientes
     */
    private function validarLimiteRetirarIngredientes(Request $request, array $ingredientes): void
    {
        if ($ingredientes === []) {
            return;
        }

        Validator::make($request->all(), [
            'max_ingredientes_retirar' => ['required', 'integer', 'min:0', 'max:'.count($ingredientes)],
        ], [
            'max_ingredientes_retirar.required' => 'Informe quantos ingredientes o cliente pode pedir para retirar (0 = nenhum).',
        ])->validate();
    }

    /**
     * @param  list<string>  $nomes
     */
    private function syncIngredientesDoProduto(Produto $produto, array $nomes): void
    {
        $produto->ingredientes()->delete();
        foreach ($nomes as $i => $nome) {
            ProdutoIngrediente::query()->create([
                'produto_id' => $produto->id,
                'nome' => $nome,
                'ordem' => $i,
            ]);
        }
    }

    private function syncAdicionaisDoProduto(Produto $produto, Empresa $empresa, Request $request): void
    {
        if (! $produto->permite_adicionais) {
            $produto->adicionais()->detach();

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
                $dst = imagecreatetruecolor($w, $h);
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $w, $h, $white);
                imagecopy($dst, $img, 0, 0, 0, 0, $w, $h);

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
