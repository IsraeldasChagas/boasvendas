<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo fiscal (estrutura preparatória — sem integração real com SEFAZ/API).
 * unidade_id reservado para evolução multi-unidade (sem FK até existir tabela de unidades).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->boolean('modulo_ativo')->default(false);
            $table->string('modo_emissao', 32)->default('nao_emitir');
            $table->string('tipo_documento', 16)->default('nfce');
            $table->string('ambiente', 16)->default('homologacao');
            $table->string('emissor_driver_padrao', 32)->nullable();
            $table->unsignedBigInteger('unidade_id')->nullable()->index();
            $table->timestamps();

            $table->unique('empresa_id');
        });

        Schema::create('fiscal_empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('razao_social', 180);
            $table->string('nome_fantasia', 180)->nullable();
            $table->string('cnpj', 18);
            $table->string('inscricao_estadual', 32)->nullable();
            $table->string('regime_tributario', 32)->nullable();
            $table->string('csc', 120)->nullable();
            $table->string('csc_id', 32)->nullable();
            $table->string('ambiente', 16)->default('homologacao');
            $table->string('certificado_path', 512)->nullable();
            $table->text('certificado_senha')->nullable();
            $table->string('emissor_tipo', 32)->default('interno');
            $table->string('api_url', 512)->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('ativo')->default(true);
            $table->unsignedBigInteger('unidade_id')->nullable()->index();
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
        });

        Schema::create('fiscal_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('tipo_nota', 16)->default('nfce');
            $table->string('numero_nota', 32)->nullable();
            $table->string('serie', 8)->nullable();
            $table->string('chave_acesso', 60)->nullable()->index();
            $table->string('protocolo', 60)->nullable();
            $table->string('status', 32)->default('nao_emitida');
            $table->string('xml_path', 512)->nullable();
            $table->string('danfe_path', 512)->nullable();
            $table->text('motivo_rejeicao')->nullable();
            $table->timestamp('data_emissao')->nullable();
            $table->decimal('valor_total', 14, 2)->nullable();
            $table->string('ambiente', 16)->nullable();
            $table->json('payload_json')->nullable();
            $table->json('retorno_json')->nullable();
            $table->unsignedBigInteger('unidade_id')->nullable()->index();
            $table->timestamps();

            $table->index(['empresa_id', 'status', 'created_at']);
            $table->index(['empresa_id', 'pedido_id']);
            $table->unique('pedido_id');
        });

        Schema::create('fiscal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('nota_id')->nullable()->constrained('fiscal_notas')->nullOnDelete();
            $table->string('tipo', 32);
            $table->text('mensagem')->nullable();
            $table->json('payload')->nullable();
            $table->json('retorno')->nullable();
            $table->unsignedBigInteger('unidade_id')->nullable()->index();
            $table->timestamps();

            $table->index(['empresa_id', 'tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_logs');
        Schema::dropIfExists('fiscal_notas');
        Schema::dropIfExists('fiscal_empresas');
        Schema::dropIfExists('fiscal_configuracoes');
    }
};
