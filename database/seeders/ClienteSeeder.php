<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('nome', 'Lanchonete Demo')->first();
        if (! $empresa) {
            return;
        }

        $rows = [
            [
                'nome' => 'Ana Costa',
                'telefone' => '(11) 98888-7777',
                'email' => 'ana.costa@example.com',
                'documento' => null,
                'observacoes' => null,
                'ativo' => true,
            ],
            [
                'nome' => 'João P.',
                'telefone' => '(11) 97777-6666',
                'email' => null,
                'documento' => null,
                'observacoes' => null,
                'ativo' => true,
            ],
        ];

        foreach ($rows as $row) {
            Cliente::query()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'nome' => $row['nome']],
                $row + ['empresa_id' => $empresa->id]
            );
        }
    }
}
