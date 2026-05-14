<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->boolean('fiscal_quer_cpf_nota')->default(false)->after('observacoes');
            $table->string('fiscal_documento', 20)->nullable()->after('fiscal_quer_cpf_nota');
            $table->string('fiscal_razao_social', 180)->nullable()->after('fiscal_documento');
            $table->string('fiscal_email', 180)->nullable()->after('fiscal_razao_social');
            $table->string('fiscal_status', 32)->default('sem_nota')->after('fiscal_email');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn([
                'fiscal_quer_cpf_nota',
                'fiscal_documento',
                'fiscal_razao_social',
                'fiscal_email',
                'fiscal_status',
            ]);
        });
    }
};
