<?php

namespace App\Http\Controllers\Empresa;

use App\Enums\Estoque\UnidadeMedida;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Insumo;
use App\Services\Estoque\EstoqueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InsumoController extends Controller
{
    public function __construct(private readonly EstoqueService $estoque) {}

    public function index(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa para gerenciar insumos.');
        }

        try {
            $query = Insumo::query()->where('empresa_id', $empresa->id)->orderBy('nome');

            if ($request->filled('q')) {
                $query->where('nome', 'like', '%'.$request->string('q')->trim().'%');
            }

            $insumos = $query->get();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('empresa.dashboard')->with(
                'warning',
                'Não foi possível abrir os insumos. Confira se a tabela existe (php artisan migrate) e o log em storage/logs/laravel.log.'
            );
        }

        return view('empresa.insumos.index', [
            'empresa' => $empresa,
            'insumos' => $insumos,
            'abaixoMinimo' => $insumos->filter(fn (Insumo $i) => $i->abaixoDoMinimo())->count(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        return view('empresa.insumos.form', [
            'empresa' => $empresa,
            'insumo' => new Insumo(['unidade_base' => UnidadeMedida::Grama, 'ativo' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        if (! $empresa) {
            return redirect()->route('empresa.dashboard')->with('warning', 'Vincule sua empresa.');
        }

        $data = $this->validated($request, $empresa);
        $saldoInicial = (float) ($data['saldo_inicial'] ?? 0);
        unset($data['saldo_inicial']);

        $data['empresa_id'] = $empresa->id;
        $data['saldo'] = 0;
        if ($request->hasFile('foto')) {
            $data['foto'] = $this->armazenarFoto($request->file('foto'), $empresa);
        }

        $insumo = Insumo::query()->create($data);

        if ($saldoInicial > 0) {
            $this->estoque->reporInsumo(
                $insumo,
                $saldoInicial,
                UnidadeMedida::from($data['unidade_base']),
                'Saldo inicial do cadastro',
                $request->user()?->id,
            );
        }

        return redirect()->route('empresa.insumos.index')->with('status', 'Insumo cadastrado.');
    }

    public function edit(Request $request, Insumo $insumo): View
    {
        $empresa = $this->autoriza($request, $insumo);

        return view('empresa.insumos.form', compact('empresa', 'insumo'));
    }

    public function update(Request $request, Insumo $insumo): RedirectResponse
    {
        $empresa = $this->autoriza($request, $insumo);

        $data = $this->validated($request, $empresa, $insumo);
        // Saldo muda apenas por reposição/ajuste (histórico auditável).
        unset($data['saldo_inicial'], $data['saldo']);

        if ($request->hasFile('foto')) {
            $this->removerFoto($insumo);
            $data['foto'] = $this->armazenarFoto($request->file('foto'), $empresa);
        }

        $insumo->update($data);

        return redirect()->route('empresa.insumos.index')->with('status', 'Insumo atualizado.');
    }

    public function destroy(Request $request, Insumo $insumo): RedirectResponse
    {
        $this->autoriza($request, $insumo);

        if ($insumo->fichaItens()->exists()) {
            return back()->with('warning', 'Este insumo está em uma ficha técnica. Remova-o das receitas antes de excluir.');
        }

        $this->removerFoto($insumo);
        $insumo->delete();

        return redirect()->route('empresa.insumos.index')->with('status', 'Insumo removido.');
    }

    public function repor(Request $request, Insumo $insumo): RedirectResponse
    {
        $this->autoriza($request, $insumo);

        $data = $request->validate([
            'quantidade' => ['required', 'numeric', 'min:0.001', 'max:9999999'],
            'unidade' => ['required', Rule::enum(UnidadeMedida::class)],
            'observacao' => ['nullable', 'string', 'max:300'],
        ]);

        $this->estoque->reporInsumo(
            $insumo,
            (float) $data['quantidade'],
            UnidadeMedida::from($data['unidade']),
            $data['observacao'] ?? null,
            $request->user()?->id,
        );

        return back()->with('status', 'Entrada registrada em "'.$insumo->nome.'".');
    }

    public function ajustar(Request $request, Insumo $insumo): RedirectResponse
    {
        $this->autoriza($request, $insumo);

        $data = $request->validate([
            'novo_saldo' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'unidade' => ['required', Rule::enum(UnidadeMedida::class)],
            'observacao' => ['nullable', 'string', 'max:300'],
        ]);

        $this->estoque->ajustarInsumo(
            $insumo,
            (float) $data['novo_saldo'],
            UnidadeMedida::from($data['unidade']),
            $data['observacao'] ?? null,
            $request->user()?->id,
        );

        return back()->with('status', 'Saldo de "'.$insumo->nome.'" ajustado.');
    }

    public function movimentos(Request $request, Insumo $insumo): View
    {
        $empresa = $this->autoriza($request, $insumo);

        return view('empresa.insumos.movimentos', [
            'empresa' => $empresa,
            'insumo' => $insumo,
            'movimentos' => $insumo->movimentos()->with('user')->limit(100)->get(),
            'usadoEm' => $insumo->fichaItens()->with('produto')->get(),
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, Empresa $empresa, ?Insumo $insumo = null): array
    {
        return $request->validate([
            'nome' => [
                'required',
                'string',
                'max:140',
                Rule::unique('insumos', 'nome')->where('empresa_id', $empresa->id)->ignore($insumo?->id),
            ],
            'unidade_base' => ['required', Rule::in(array_map(fn ($u) => $u->value, UnidadeMedida::basesDisponiveis()))],
            'saldo_inicial' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'estoque_minimo' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'custo_unitario' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'ativo' => ['sometimes', 'boolean'],
            'foto' => ['nullable', 'image', 'max:3072'],
        ], [
            'nome.unique' => 'Já existe um insumo com este nome.',
            'unidade_base.in' => 'Escolha peso (g), volume (ml) ou contagem (un).',
        ]) + ['ativo' => $request->boolean('ativo', true)];
    }

    private function armazenarFoto(UploadedFile $file, Empresa $empresa): string
    {
        $dir = 'insumos/'.$empresa->id;
        $nome = Str::uuid()->toString().'.jpg';
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
            // Fallback: grava o arquivo original.
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'jpg';
        $fallback = Str::uuid()->toString().'.'.$ext;
        $file->storeAs($dir, $fallback, 'uploads');

        return $dir.'/'.$fallback;
    }

    private function removerFoto(Insumo $insumo): void
    {
        if (! filled($insumo->foto)) {
            return;
        }

        $path = ltrim(str_replace('\\', '/', (string) $insumo->foto), '/');
        if (Storage::disk('uploads')->exists($path)) {
            Storage::disk('uploads')->delete($path);
        }
    }

    private function autoriza(Request $request, Insumo $insumo): Empresa
    {
        $empresa = $request->user()->empresa;
        abort_unless($empresa && (int) $insumo->empresa_id === (int) $empresa->id, 403);

        return $empresa;
    }
}
