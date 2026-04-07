<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Recria a empresa "Lanchonete Demo" (slug demo), categorias, produtos, VE demo, etc.
 * Idempotente: pode correr várias vezes (usa firstOrCreate nos seeders base).
 */
class RestaurarEmpresaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanoSeeder::class);
        $this->call(ModuloSeeder::class);
        $this->call(EmpresaSeeder::class);
        $this->call(AssinaturaSeeder::class);
        $this->call(SuporteTicketSeeder::class);
        $this->call(CategoriaSeeder::class);
        $this->call(ProdutoSeeder::class);
        $this->call(ClienteSeeder::class);
        $this->call(FidelidadeProgramaSeeder::class);
        $this->call(FinanceiroTituloSeeder::class);
        $this->call(CaixaSeeder::class);
        $this->call(VendaExternaDemoSeeder::class);
    }
}
