<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\FinanceiroTitulo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FinanceiroTituloSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('slug', 'demo')->first();
        if (! $empresa) {
            return;
        }

        $rows = [
            [
                'tipo' => FinanceiroTitulo::TIPO_RECEBER,
                'contraparte' => 'Mercado Z',
                'descricao' => 'Pedido #10410',
                'valor' => 120.00,
                'vencimento' => Carbon::now()->addDays(5),
                'status' => FinanceiroTitulo::STATUS_ABERTO,
                'pago_em' => null,
            ],
            [
                'tipo' => FinanceiroTitulo::TIPO_RECEBER,
                'contraparte' => 'Ana C.',
                'descricao' => 'Fiado parcial',
                'valor' => 40.00,
                'vencimento' => Carbon::now()->subDays(3),
                'status' => FinanceiroTitulo::STATUS_ABERTO,
                'pago_em' => null,
            ],
            [
                'tipo' => FinanceiroTitulo::TIPO_PAGAR,
                'contraparte' => 'Distribuidora X',
                'descricao' => 'Compra semanal',
                'valor' => 2400.00,
                'vencimento' => Carbon::now()->addDays(6),
                'status' => FinanceiroTitulo::STATUS_ABERTO,
                'pago_em' => null,
            ],
            [
                'tipo' => FinanceiroTitulo::TIPO_PAGAR,
                'contraparte' => 'Energia',
                'descricao' => 'Fatura março',
                'valor' => 380.00,
                'vencimento' => Carbon::now()->subMonth()->day(12),
                'status' => FinanceiroTitulo::STATUS_PAGO,
                'pago_em' => Carbon::now()->subMonth()->day(10),
            ],
        ];

        foreach ($rows as $row) {
            FinanceiroTitulo::query()->firstOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'tipo' => $row['tipo'],
                    'descricao' => $row['descricao'],
                    'contraparte' => $row['contraparte'],
                ],
                $row + ['empresa_id' => $empresa->id, 'observacoes' => null]
            );
        }

        for ($m = 5; $m >= 0; $m--) {
            $ref = Carbon::now()->subMonths($m)->startOfMonth();
            FinanceiroTitulo::query()->firstOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'tipo' => FinanceiroTitulo::TIPO_RECEBER,
                    'descricao' => 'Receita demo '.$ref->format('Y-m'),
                ],
                [
                    'contraparte' => 'Vendas diversas',
                    'valor' => 800 + ($m * 120),
                    'vencimento' => $ref->copy()->day(20),
                    'status' => FinanceiroTitulo::STATUS_PAGO,
                    'pago_em' => $ref->copy()->day(15),
                    'observacoes' => null,
                ]
            );

            FinanceiroTitulo::query()->firstOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'tipo' => FinanceiroTitulo::TIPO_PAGAR,
                    'descricao' => 'Despesas demo '.$ref->format('Y-m'),
                ],
                [
                    'contraparte' => 'Fornecedores',
                    'valor' => 400 + ($m * 80),
                    'vencimento' => $ref->copy()->day(22),
                    'status' => FinanceiroTitulo::STATUS_PAGO,
                    'pago_em' => $ref->copy()->day(18),
                    'observacoes' => null,
                ]
            );
        }
    }
}
