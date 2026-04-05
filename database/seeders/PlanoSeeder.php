<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'nome' => 'Essencial',
                'preco_mensal' => 49,
                'feature_primaria' => '200 produtos',
                'feature_secundaria' => 'Suporte e-mail',
                'ordem' => 1,
            ],
            [
                'nome' => 'Crescimento',
                'preco_mensal' => 99,
                'feature_primaria' => 'Venda externa + filiais',
                'feature_secundaria' => 'Chat prioritário',
                'ordem' => 2,
            ],
            [
                'nome' => 'Franquia',
                'preco_mensal' => 249,
                'feature_primaria' => 'Tudo + SLA',
                'feature_secundaria' => 'Gerente dedicado',
                'ordem' => 3,
            ],
        ];

        foreach ($defaults as $row) {
            Plano::query()->firstOrCreate(
                ['nome' => $row['nome']],
                $row + ['ativo' => true]
            );
        }
    }
}
