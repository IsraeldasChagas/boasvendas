<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;

class ModuloSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'nome' => 'Delivery & loja pública',
                'categoria' => 'Core',
                'situacao' => 'ativo',
                'ordem' => 1,
            ],
            [
                'nome' => 'Venda externa / consignação',
                'categoria' => 'Premium',
                'situacao' => 'addon',
                'ordem' => 2,
            ],
            [
                'nome' => 'Financeiro avançado',
                'categoria' => 'Crescimento+',
                'situacao' => 'addon',
                'ordem' => 3,
            ],
            [
                'nome' => 'API pública',
                'categoria' => '',
                'situacao' => 'roadmap',
                'ordem' => 4,
            ],
        ];

        foreach ($rows as $row) {
            Modulo::query()->firstOrCreate(
                ['nome' => $row['nome']],
                $row
            );
        }
    }
}
