<?php

namespace Database\Seeders;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Plano;
use Illuminate\Database\Seeder;

class AssinaturaSeeder extends Seeder
{
    public function run(): void
    {
        $crescimento = Plano::query()->where('nome', 'Crescimento')->first();
        $essencial = Plano::query()->where('nome', 'Essencial')->first();

        $rows = [
            [
                'empresa_nome' => 'Lanchonete Demo',
                'plano_id' => $crescimento?->id,
                'valor_mensal' => 99,
                'proxima_cobranca' => '2026-05-05',
                'gateway' => 'Stripe (mock)',
                'status' => 'paga',
            ],
            [
                'empresa_nome' => 'Pastelaria Centro',
                'plano_id' => $essencial?->id,
                'valor_mensal' => 49,
                'proxima_cobranca' => '2026-04-10',
                'gateway' => '',
                'status' => 'pendente',
            ],
        ];

        foreach ($rows as $row) {
            $row['empresa_id'] = Empresa::query()->where('nome', $row['empresa_nome'])->value('id');
            Assinatura::query()->firstOrCreate(
                ['empresa_nome' => $row['empresa_nome']],
                $row
            );
        }
    }
}
