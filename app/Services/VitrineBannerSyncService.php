<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaLojaBannerImagem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VitrineBannerSyncService
{
    /**
     * @param  iterable<int, Empresa>  $targets
     * @return array{empresas: int, imagens_total: int, categorias_aplicadas: int, categorias_sem_match: int}
     */
    public function sincronizar(Empresa $origem, iterable $targets, bool $substituirExistente): array
    {
        $origem->loadMissing('lojaBannerCategoria');
        $nomeCategoriaOrigem = null;
        $catOrig = $origem->lojaBannerCategoria;
        if ($catOrig !== null && (int) $catOrig->empresa_id === (int) $origem->id) {
            $nomeCategoriaOrigem = trim((string) $catOrig->nome);
            if ($nomeCategoriaOrigem === '') {
                $nomeCategoriaOrigem = null;
            }
        }

        $imagensOrigem = collect();
        if (Schema::hasTable('empresa_loja_banner_imagens')) {
            $imagensOrigem = EmpresaLojaBannerImagem::query()
                ->where('empresa_id', $origem->id)
                ->orderBy('ordem')
                ->orderBy('id')
                ->get();
        }

        $empresas = 0;
        $imagensTotal = 0;
        $catAplicadas = 0;
        $catSemMatch = 0;

        foreach ($targets as $destino) {
            if ((int) $destino->id === (int) $origem->id) {
                continue;
            }

            DB::transaction(function () use ($destino, $substituirExistente, $imagensOrigem, $nomeCategoriaOrigem, &$empresas, &$imagensTotal, &$catAplicadas, &$catSemMatch) {
                if (Schema::hasTable('empresa_loja_banner_imagens')) {
                    if ($substituirExistente) {
                        $this->apagarImagensBannerEmpresa($destino);
                    }
                    if ($substituirExistente || EmpresaLojaBannerImagem::query()->where('empresa_id', $destino->id)->doesntExist()) {
                        $ordem = 0;
                        foreach ($imagensOrigem as $row) {
                            if ($ordem >= 12) {
                                break;
                            }
                            $abs = $row->resolveAbsolutePath();
                            if ($abs === null || ! is_file($abs)) {
                                continue;
                            }
                            $ext = pathinfo($abs, PATHINFO_EXTENSION) ?: 'jpg';
                            $ext = strtolower((string) $ext);
                            $ext = preg_match('/^[a-z0-9]{2,4}$/', $ext) ? $ext : 'jpg';
                            $nome = Str::uuid()->toString().'.'.$ext;
                            $dir = 'empresas/'.$destino->id.'/loja-banner';
                            $relNovo = $dir.'/'.$nome;
                            $disk = Storage::disk('uploads');
                            $disk->makeDirectory($dir);
                            $conteudo = @file_get_contents($abs);
                            if ($conteudo === false) {
                                continue;
                            }
                            $disk->put($relNovo, $conteudo);
                            EmpresaLojaBannerImagem::query()->create([
                                'empresa_id' => $destino->id,
                                'caminho' => $relNovo,
                                'ordem' => $ordem,
                            ]);
                            $ordem++;
                            $imagensTotal++;
                        }
                    }
                }

                if (Empresa::schemaTemColunaLojaBannerCategoria()) {
                    if ($nomeCategoriaOrigem === null) {
                        $destino->forceFill(['loja_banner_categoria_id' => null])->save();
                    } else {
                        $match = Categoria::query()
                            ->where('empresa_id', $destino->id)
                            ->where('ativo', true)
                            ->whereRaw('LOWER(TRIM(nome)) = ?', [mb_strtolower($nomeCategoriaOrigem)])
                            ->orderBy('ordem')
                            ->orderBy('id')
                            ->first();
                        if ($match !== null) {
                            $destino->forceFill(['loja_banner_categoria_id' => $match->id])->save();
                            $catAplicadas++;
                        } else {
                            $destino->forceFill(['loja_banner_categoria_id' => null])->save();
                            $catSemMatch++;
                        }
                    }
                }

                $empresas++;
            });
        }

        return [
            'empresas' => $empresas,
            'imagens_total' => $imagensTotal,
            'categorias_aplicadas' => $catAplicadas,
            'categorias_sem_match' => $catSemMatch,
        ];
    }

    private function apagarImagensBannerEmpresa(Empresa $empresa): void
    {
        $rows = EmpresaLojaBannerImagem::query()->where('empresa_id', $empresa->id)->get();
        foreach ($rows as $row) {
            $path = ltrim(str_replace('\\', '/', (string) $row->caminho), '/');
            if ($path !== '' && Storage::disk('uploads')->exists($path)) {
                Storage::disk('uploads')->delete($path);
            }
            $row->delete();
        }
    }
}
