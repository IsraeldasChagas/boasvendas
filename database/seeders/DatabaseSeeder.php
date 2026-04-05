<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Usuários de demonstração (senha: password).
     * Master: defina o mesmo e-mail em BOASVENDAS_ADMIN_EMAILS no .env
     */
    public function run(): void
    {
        $this->call(PlanoSeeder::class);
        $this->call(ModuloSeeder::class);
        $this->call(AssinaturaSeeder::class);
        $this->call(EmpresaSeeder::class);
        $this->call(SuporteTicketSeeder::class);
        $this->call(CategoriaSeeder::class);
        $this->call(ProdutoSeeder::class);
        $this->call(ClienteSeeder::class);
        $this->call(FidelidadeProgramaSeeder::class);
        $this->call(FinanceiroTituloSeeder::class);
        $this->call(CaixaSeeder::class);
        $this->call(VendaExternaDemoSeeder::class);

        User::query()->firstOrCreate(
            ['email' => 'admin@boavendas.app'],
            [
                'name' => 'Admin Master',
                'password' => Hash::make('password'),
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'empresa@boavendas.app'],
            [
                'name' => 'Empresa Demo',
                'password' => Hash::make('password'),
            ]
        );

        $lanchoneteId = Empresa::query()->where('nome', 'Lanchonete Demo')->value('id');
        if ($lanchoneteId) {
            User::query()->where('email', 'empresa@boavendas.app')->update([
                'empresa_id' => $lanchoneteId,
                'role' => 'gestor',
            ]);
        }
    }
}
