<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\SuporteTicket;
use Illuminate\Database\Seeder;

class SuporteTicketSeeder extends Seeder
{
    public function run(): void
    {
        $lanchonete = Empresa::query()->where('nome', 'Lanchonete Demo')->first();
        $pastelaria = Empresa::query()->where('nome', 'Pastelaria Centro')->first();

        $rows = [
            [
                'empresa_id' => $lanchonete?->id,
                'assunto' => 'Dúvida sobre remessas',
                'descricao' => 'Cliente precisa de orientação sobre o fluxo de remessas na venda externa.',
                'prioridade' => 'media',
                'status' => 'aberto',
            ],
            [
                'empresa_id' => $pastelaria?->id,
                'assunto' => 'Erro ao salvar produto',
                'descricao' => 'Ao cadastrar produto com imagem, a tela retorna erro 500 (exemplo).',
                'prioridade' => 'alta',
                'status' => 'aguardando',
            ],
        ];

        foreach ($rows as $row) {
            SuporteTicket::query()->firstOrCreate(
                ['assunto' => $row['assunto'], 'empresa_id' => $row['empresa_id']],
                $row
            );
        }
    }
}
