<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->string('empresa_nome');
            $table->foreignId('plano_id')->nullable()->constrained('planos')->nullOnDelete();
            $table->decimal('valor_mensal', 10, 2);
            $table->date('proxima_cobranca');
            $table->string('gateway')->nullable();
            $table->string('status', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assinaturas');
    }
};
