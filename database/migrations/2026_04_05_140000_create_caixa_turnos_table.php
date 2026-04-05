<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caixa_turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('aberto_em');
            $table->dateTime('fechado_em')->nullable();
            $table->decimal('valor_abertura', 12, 2)->default(0);
            $table->decimal('valor_conferido_fechamento', 12, 2)->nullable();
            $table->string('status', 16)->default('aberto');
            $table->text('obs_abertura')->nullable();
            $table->text('obs_fechamento')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caixa_turnos');
    }
};
