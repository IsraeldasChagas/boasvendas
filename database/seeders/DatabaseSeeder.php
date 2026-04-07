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
     * Também criados pela migration `2026_04_07_120000_ensure_demo_users` (sem precisar de db:seed).
     * Master: defina o mesmo e-mail em VENDAFFACIL_ADMIN_EMAILS no .env
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
            ['email' => 'master@vendaffacil.com.br'],
            [
                'name' => 'Master',
                'password' => Hash::make('password'),
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'empresa@vendaffacil.com.br'],
            [
                'name' => 'Empresa Demo',
                'password' => Hash::make('password'),
            ]
        );

        $lanchoneteId = Empresa::query()->where('nome', 'Lanchonete Demo')->value('id');
        if ($lanchoneteId) {
            User::query()->where('email', 'empresa@vendaffacil.com.br')->update([
                'empresa_id' => $lanchoneteId,
                'role' => 'gestor',
            ]);
        }
    }
}
