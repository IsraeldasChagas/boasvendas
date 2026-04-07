<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\VeAcerto;
use App\Models\VeFiado;
use App\Models\VePonto;
use App\Models\VeRemessa;
use App\Models\VeVendaExternaRegistro;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VendaExternaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('slug', 'demo')->first();
        if (! $empresa) {
            return;
        }

        Carbon::setLocale('pt_BR');

        $pFeira = VePonto::query()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'Feira Sul'],
            [
                'regiao' => 'Zona Sul',
                'status' => VePonto::STATUS_ATIVO,
                'proximo_acerto_em' => Carbon::now()->next(Carbon::FRIDAY)->setTime(14, 0),
                'ultimo_acerto_em' => Carbon::now()->subDays(3),
            ]
        );

        $pBar = VePonto::query()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'Bar do Zé'],
            [
                'regiao' => 'Centro',
                'status' => VePonto::STATUS_ATIVO,
                'proximo_acerto_em' => Carbon::now()->next(Carbon::SATURDAY)->setTime(10, 0),
                'ultimo_acerto_em' => Carbon::now()->subDays(7),
            ]
        );

        $pBanca = VePonto::query()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'nome' => 'Banca 12'],
            [
                'regiao' => 'Terminal',
                'status' => VePonto::STATUS_PAUSADO,
                'proximo_acerto_em' => null,
                'ultimo_acerto_em' => Carbon::now()->subDays(12),
            ]
        );

        $pontos = [$pFeira, $pBar, $pBanca];

        if (VeRemessa::query()->where('empresa_id', $empresa->id)->doesntExist()) {
            $titulos = ['Mix salgados', 'Doces sortidos', 'Bebidas geladas', 'Reposição combo', 'Extra fim de semana'];
            foreach ($titulos as $i => $t) {
                VeRemessa::query()->create([
                    'empresa_id' => $empresa->id,
                    've_ponto_id' => $pontos[$i % 3]->id,
                    'titulo' => $t,
                    'status' => $i < 5 ? VeRemessa::STATUS_EM_CAMPO : VeRemessa::STATUS_PREPARACAO,
                ]);
            }
            VeRemessa::query()->create([
                'empresa_id' => $empresa->id,
                've_ponto_id' => $pFeira->id,
                'titulo' => 'Pedido especial',
                'status' => VeRemessa::STATUS_PREPARACAO,
            ]);
        }

        if (VeFiado::query()->where('empresa_id', $empresa->id)->doesntExist()) {
            VeFiado::query()->create([
                'empresa_id' => $empresa->id,
                've_ponto_id' => $pFeira->id,
                'contraparte' => 'Feira Sul',
                'descricao' => 'Fechamento parcial',
                'valor' => 3200.00,
                'status' => VeFiado::STATUS_ABERTO,
                'vencimento' => Carbon::now()->addDays(10),
            ]);
            VeFiado::query()->create([
                'empresa_id' => $empresa->id,
                've_ponto_id' => $pBar->id,
                'contraparte' => 'Bar do Zé',
                'descricao' => 'Consignação mês',
                'valor' => 5220.00,
                'status' => VeFiado::STATUS_ABERTO,
                'vencimento' => Carbon::now()->addDays(18),
            ]);
        }

        if (VeVendaExternaRegistro::query()->where('empresa_id', $empresa->id)->doesntExist()) {
            $weekStart = Carbon::now()->subWeeks(11)->startOfWeek();
            for ($w = 0; $w < 12; $w++) {
                $ws = $weekStart->copy()->addWeeks($w);
                $n = 1 + ($w % 3);
                for ($k = 0; $k < $n; $k++) {
                    $dia = $ws->copy()->addDays(1 + $k * 2);
                    VeVendaExternaRegistro::query()->create([
                        'empresa_id' => $empresa->id,
                        've_ponto_id' => $pontos[($w + $k) % 2]->id,
                        'data_venda' => $dia->toDateString(),
                        'valor' => 420 + ($w * 95) + ($k * 110),
                        'referencia' => 'Sem. '.($w + 1),
                    ]);
                }
            }
        }

        if (VeAcerto::query()->where('empresa_id', $empresa->id)->doesntExist()) {
            $remessaBar = VeRemessa::query()->where('empresa_id', $empresa->id)->where('ve_ponto_id', $pBar->id)->orderBy('id')->first();
            $remessaFeira = VeRemessa::query()->where('empresa_id', $empresa->id)->where('ve_ponto_id', $pFeira->id)->orderBy('id')->first();

            VeAcerto::query()->create([
                'empresa_id' => $empresa->id,
                've_ponto_id' => $pBar->id,
                've_remessa_id' => $remessaBar?->id,
                'data_acerto' => Carbon::now()->subDays(7)->toDateString(),
                'valor_vendas' => null,
                'valor_repasse_unitario' => 5.40,
                'valor_repasse' => 486.00,
                'status' => VeAcerto::STATUS_CONCLUIDO,
                'observacoes' => null,
            ]);

            VeAcerto::query()->create([
                'empresa_id' => $empresa->id,
                've_ponto_id' => $pFeira->id,
                've_remessa_id' => $remessaFeira?->id,
                'data_acerto' => null,
                'valor_vendas' => null,
                'valor_repasse_unitario' => null,
                'valor_repasse' => null,
                'status' => VeAcerto::STATUS_ABERTO,
                'observacoes' => null,
            ]);
        }
    }
}
