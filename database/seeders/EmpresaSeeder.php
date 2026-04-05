<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Plano;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $crescimento = Plano::query()->where('nome', 'Crescimento')->first();
        $essencial = Plano::query()->where('nome', 'Essencial')->first();
        $franquia = Plano::query()->where('nome', 'Franquia')->first();

        $rows = [
            [
                'nome' => 'Lanchonete Demo',
                'slug' => 'demo',
                'email_contato' => 'lanchonete-demo@boavendas.app',
                'cnpj' => null,
                'plano_id' => $crescimento?->id,
                'status' => 'ativa',
                'modulos_resumo' => 'VE + Delivery',
                'cliente_desde' => '2025-01-12',
            ],
            [
                'nome' => 'Pastelaria Centro',
                'slug' => 'pastelaria-centro',
                'email_contato' => null,
                'cnpj' => null,
                'plano_id' => $essencial?->id,
                'status' => 'trial',
                'modulos_resumo' => 'Delivery',
                'cliente_desde' => null,
            ],
            [
                'nome' => 'Distribuidora X',
                'slug' => 'distribuidora-x',
                'email_contato' => null,
                'cnpj' => null,
                'plano_id' => $franquia?->id,
                'status' => 'ativa',
                'modulos_resumo' => 'Todos',
                'cliente_desde' => null,
            ],
        ];

        foreach ($rows as $row) {
            $empresa = Empresa::query()->firstOrCreate(
                ['nome' => $row['nome']],
                $row
            );
            if ($empresa->slug !== $row['slug']) {
                $empresa->update(['slug' => $row['slug']]);
            }
        }
    }
}
