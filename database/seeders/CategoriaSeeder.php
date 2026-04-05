<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('nome', 'Lanchonete Demo')->first();
        if (! $empresa) {
            return;
        }

        $lista = [
            ['nome' => 'Destaques', 'ordem' => 10],
            ['nome' => 'Lanches', 'ordem' => 20],
            ['nome' => 'Bebidas', 'ordem' => 30],
            ['nome' => 'Sobremesas', 'ordem' => 40],
            ['nome' => 'Sobremesa', 'ordem' => 41],
            ['nome' => 'Promoções', 'ordem' => 50],
        ];

        foreach ($lista as $row) {
            Categoria::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'nome' => $row['nome']],
                ['ordem' => $row['ordem'], 'ativo' => true]
            );
        }
    }
}
