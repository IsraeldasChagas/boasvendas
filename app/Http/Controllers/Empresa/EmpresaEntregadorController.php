<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\EmpresaEntregador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmpresaEntregadorController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! Schema::hasTable('empresa_entregadores')) {
            return redirect()
                ->route('empresa.configuracoes.index')
                ->with('warning', 'Execute as migrations para usar o cadastro de entregadores.');
        }

        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $entregadores = EmpresaEntregador::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('empresa.entregadores.index', compact('empresa', 'entregadores'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! Schema::hasTable('empresa_entregadores')) {
            return redirect()
                ->route('empresa.configuracoes.index')
                ->with('warning', 'Execute as migrations para usar o cadastro de entregadores.');
        }

        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.entregadores.create', compact('empresa'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('empresa_entregadores')) {
            return redirect()
                ->route('empresa.configuracoes.index')
                ->with('warning', 'Execute as migrations para usar o cadastro de entregadores.');
        }

        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request, true);
        $data['empresa_id'] = $empresa->id;
        $data['ativo'] = $request->boolean('ativo');

        $foto = $request->file('foto');
        if ($foto instanceof UploadedFile) {
            $data['foto'] = $this->armazenarFoto($foto, $empresa->id);
        } else {
            unset($data['foto']);
        }

        EmpresaEntregador::query()->create($data);

        return redirect()
            ->route('empresa.entregadores.index')
            ->with('status', 'Entregador cadastrado.');
    }

    public function edit(Request $request, EmpresaEntregador $entregador): View|RedirectResponse
    {
        if (! Schema::hasTable('empresa_entregadores')) {
            return redirect()
                ->route('empresa.configuracoes.index')
                ->with('warning', 'Execute as migrations para usar o cadastro de entregadores.');
        }

        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $entregador->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        return view('empresa.entregadores.edit', compact('empresa', 'entregador'));
    }

    public function update(Request $request, EmpresaEntregador $entregador): RedirectResponse
    {
        if (! Schema::hasTable('empresa_entregadores')) {
            return redirect()
                ->route('empresa.configuracoes.index')
                ->with('warning', 'Execute as migrations para usar o cadastro de entregadores.');
        }

        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $entregador->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $data = $this->validated($request, false);
        $data['ativo'] = $request->boolean('ativo');

        if ($request->boolean('remover_foto')) {
            $this->removerFotoDoDisco($entregador);
            $data['foto'] = null;
        }

        $foto = $request->file('foto');
        if ($foto instanceof UploadedFile) {
            $this->removerFotoDoDisco($entregador);
            $data['foto'] = $this->armazenarFoto($foto, $empresa->id);
        } else {
            unset($data['foto']);
        }

        $entregador->update($data);

        return redirect()
            ->route('empresa.entregadores.index')
            ->with('status', 'Entregador atualizado.');
    }

    public function destroy(Request $request, EmpresaEntregador $entregador): RedirectResponse
    {
        if (! Schema::hasTable('empresa_entregadores')) {
            return redirect()
                ->route('empresa.configuracoes.index')
                ->with('warning', 'Execute as migrations para usar o cadastro de entregadores.');
        }

        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $entregador->empresa_id !== (int) $empresa->id) {
            abort(403);
        }

        $this->removerFotoDoDisco($entregador);
        $entregador->delete();

        return redirect()
            ->route('empresa.entregadores.index')
            ->with('status', 'Entregador removido.');
    }

    public function foto(Request $request, EmpresaEntregador $entregador): BinaryFileResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa || (int) $entregador->empresa_id !== (int) $empresa->id) {
            abort(404);
        }

        $full = $entregador->resolveFotoAbsolutePath();
        if ($full === null || ! is_file($full)) {
            abort(404);
        }

        return response()->file($full, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $fotoObrigatoria): array
    {
        $fotoRules = $fotoObrigatoria
            ? ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']
            : ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:32'],
            'foto' => $fotoRules,
            'moto_modelo' => ['nullable', 'string', 'max:120'],
            'moto_cor' => ['nullable', 'string', 'max:64'],
            'moto_placa' => ['nullable', 'string', 'max:16'],
            'ordem' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'remover_foto' => ['nullable', 'boolean'],
        ]);

        $data['ordem'] = isset($data['ordem']) ? (int) $data['ordem'] : 0;

        return $data;
    }

    private function armazenarFoto(UploadedFile $file, int $empresaId): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'png';
        $nome = Str::uuid()->toString().'.'.$ext;
        $dir = 'empresas/'.$empresaId.'/entregadores';

        return $file->storeAs($dir, $nome, 'uploads');
    }

    private function removerFotoDoDisco(EmpresaEntregador $entregador): void
    {
        if (! $entregador->foto) {
            return;
        }

        $path = ltrim(str_replace('\\', '/', (string) $entregador->foto), '/');

        if (Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);

            return;
        }

        Storage::disk('public')->delete($entregador->foto);
    }
}
