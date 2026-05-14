<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Adicional;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdicionalController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()
                ->route('empresa.dashboard')
                ->with('warning', 'Vincule sua empresa para gerenciar adicionais.');
        }

        $adicionais = Adicional::query()
            ->where('empresa_id', $empresa->id)
            ->withCount('produtos')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('empresa.adicionais.index', compact('empresa', 'adicionais'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.adicionais.create', compact('empresa'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request, false);
        $data['empresa_id'] = $empresa->id;
        if ($data['tipo'] === Adicional::TIPO_RETIRAR) {
            $data['preco'] = 0;
        }

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->armazenarFotoAdicional($request->file('foto'), $empresa);
        } else {
            unset($data['foto']);
        }

        Adicional::query()->create($data);

        return redirect()
            ->route('empresa.adicionais.index')
            ->with('status', 'Adicional cadastrado.');
    }

    public function edit(Request $request, Adicional $adicional): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $adicional->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        return view('empresa.adicionais.edit', compact('empresa', 'adicional'));
    }

    public function update(Request $request, Adicional $adicional): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $adicional->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $data = $this->validated($request, true);
        if ($data['tipo'] === Adicional::TIPO_RETIRAR) {
            $data['preco'] = 0;
        }

        if ($request->hasFile('foto')) {
            $this->removerFotoAdicionalDoDisco($adicional);
            $data['foto'] = $this->armazenarFotoAdicional($request->file('foto'), $empresa);
        } elseif ($request->boolean('remover_foto')) {
            $this->removerFotoAdicionalDoDisco($adicional);
            $data['foto'] = null;
        }

        $adicional->update($data);

        return redirect()
            ->route('empresa.adicionais.index')
            ->with('status', 'Adicional atualizado.');
    }

    public function destroy(Request $request, Adicional $adicional): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $adicional->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $this->removerFotoAdicionalDoDisco($adicional);
        $adicional->delete();

        return redirect()
            ->route('empresa.adicionais.index')
            ->with('status', 'Adicional removido.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $isUpdate): array
    {
        $rules = [
            'nome' => ['required', 'string', 'max:120'],
            'tipo' => ['required', 'string', Rule::in([Adicional::TIPO_ACRESCENTAR, Adicional::TIPO_RETIRAR])],
            'preco' => ['required', 'numeric', 'min:0'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'ativo' => ['sometimes', 'boolean'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];
        if ($isUpdate) {
            $rules['remover_foto'] = ['sometimes', 'boolean'];
        }

        $data = $request->validate($rules);

        $data['ativo'] = $request->boolean('ativo');
        $data['ordem'] = (int) ($data['ordem'] ?? 0);
        if ($isUpdate) {
            unset($data['remover_foto']);
        }

        return $data;
    }

    private function armazenarFotoAdicional(UploadedFile $file, Empresa $empresa): string
    {
        $nome = Str::uuid()->toString().'.jpg';
        $dir = 'adicionais/'.$empresa->id;
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

    private function removerFotoAdicionalDoDisco(Adicional $adicional): void
    {
        if (! $adicional->foto) {
            return;
        }

        $path = ltrim(str_replace('\\', '/', $adicional->foto), '/');

        if (Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);

            return;
        }

        Storage::disk('public')->delete($adicional->foto);
    }
}
