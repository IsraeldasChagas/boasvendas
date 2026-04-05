<?php

namespace Database\Seeders;

use App\Models\CaixaMovimento;
use App\Models\CaixaTurno;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CaixaSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('slug', 'demo')->first();
        if (! $empresa) {
            return;
        }

        $abertos = CaixaTurno::query()
            ->where('empresa_id', $empresa->id)
            ->where('status', CaixaTurno::STATUS_ABERTO)
            ->pluck('id');

        if ($abertos->isNotEmpty()) {
            CaixaMovimento::query()->whereIn('caixa_turno_id', $abertos)->delete();
            CaixaTurno::query()->whereIn('id', $abertos)->delete();
        }

        $turno = CaixaTurno::query()->create([
            'empresa_id' => $empresa->id,
            'user_id' => null,
            'aberto_em' => Carbon::now()->subHours(3),
            'valor_abertura' => 200,
            'status' => CaixaTurno::STATUS_ABERTO,
            'obs_abertura' => null,
        ]);

        CaixaMovimento::query()->create([
            'caixa_turno_id' => $turno->id,
            'user_id' => null,
            'tipo' => CaixaMovimento::TIPO_VENDA_AVULSA,
            'descricao' => 'Venda balcão #VF-10400',
            'valor' => 54,
        ]);
    }
}
