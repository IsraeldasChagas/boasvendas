<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Vendas por Mesa / Comanda — multiempresa e multiunidade (unidade_id sem FK até existir cadastro de unidades).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedBigInteger('unidade_id')->nullable()->index();
            $table->string('numero', 32);
            $table->string('nome', 120)->nullable();
            $table->unsignedSmallInteger('capacidade')->default(4);
            $table->string('localizacao', 120)->nullable();
            $table->string('status', 32)->default('livre');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'unidade_id', 'numero']);
            $table->index(['empresa_id', 'status', 'ativo']);
        });

        Schema::create('comandas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedBigInteger('unidade_id')->nullable()->index();
            $table->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
            $table->string('cliente_nome', 160)->nullable();
            $table->string('cliente_documento', 32)->nullable();
            $table->foreignId('garcom_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('aberta');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('taxa_servico', 14, 2)->default(0);
            $table->decimal('desconto', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('taxa_servico_percentual', 8, 2)->nullable()->comment('Percentual aplicado no fechamento (histórico)');
            $table->text('observacao')->nullable();
            $table->timestamp('aberta_em')->useCurrent();
            $table->timestamp('fechada_em')->nullable();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->json('integracao_payload')->nullable()->comment('Snapshot para financeiro/estoque/fiscal futuros');
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
            $table->index(['mesa_id', 'status']);
        });

        Schema::create('comanda_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comandas')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('nome_produto', 200);
            $table->unsignedInteger('quantidade')->default(1);
            $table->decimal('valor_unitario', 14, 2);
            $table->decimal('valor_total', 14, 2);
            $table->string('observacao', 500)->nullable();
            $table->string('setor_destino', 24)->default('cozinha');
            $table->string('status', 32)->default('pendente');
            $table->timestamp('enviado_cozinha_em')->nullable();
            $table->timestamp('pronto_em')->nullable();
            $table->timestamp('entregue_em')->nullable();
            $table->timestamps();

            $table->index(['comanda_id', 'status']);
            $table->index(['setor_destino', 'status']);
        });

        Schema::create('pagamentos_comanda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained('comandas')->cascadeOnDelete();
            $table->string('forma_pagamento', 48);
            $table->decimal('valor_pago', 14, 2);
            $table->decimal('troco', 14, 2)->default(0);
            $table->string('status', 24)->default('pendente');
            $table->timestamps();

            $table->index(['comanda_id', 'status']);
        });

        Schema::create('mesa_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedBigInteger('unidade_id')->nullable();
            $table->decimal('taxa_servico_padrao_percent', 8, 2)->default(10);
            $table->boolean('exigir_garcom_abertura')->default(false);
            $table->timestamps();

            $table->unique(['empresa_id', 'unidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos_comanda');
        Schema::dropIfExists('comanda_itens');
        Schema::dropIfExists('comandas');
        Schema::dropIfExists('mesa_configuracoes');
        Schema::dropIfExists('mesas');
    }
};
