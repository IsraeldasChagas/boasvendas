<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reparo: em produção o migrate pode ter rodado em outro diretório/.env,
 * ou Schema::hasTable falhou. Recria só o que faltar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('insumos')) {
            Schema::create('insumos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->string('nome', 140);
                $table->string('foto', 512)->nullable();
                $table->string('unidade_base', 8)->default('g');
                $table->decimal('saldo', 14, 3)->default(0);
                $table->decimal('estoque_minimo', 14, 3)->default(0);
                $table->decimal('custo_unitario', 12, 4)->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();

                $table->index(['empresa_id', 'ativo']);
                $table->unique(['empresa_id', 'nome']);
            });
        }

        if (! Schema::hasTable('produto_ficha_itens')) {
            Schema::create('produto_ficha_itens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
                $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
                $table->decimal('quantidade', 12, 3);
                $table->string('unidade', 8);
                $table->decimal('quantidade_base', 14, 3);
                $table->string('observacao', 200)->nullable();
                $table->unsignedSmallInteger('ordem')->default(0);
                $table->timestamps();

                $table->unique(['produto_id', 'insumo_id']);
                $table->index(['empresa_id', 'produto_id']);
            });
        }

        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                if (! Schema::hasColumn('produtos', 'modo_preparo')) {
                    $table->text('modo_preparo')->nullable();
                }
                if (! Schema::hasColumn('produtos', 'ficha_rendimento')) {
                    $table->unsignedInteger('ficha_rendimento')->default(1);
                }
                if (! Schema::hasColumn('produtos', 'ficha_tempo_preparo_min')) {
                    $table->unsignedSmallInteger('ficha_tempo_preparo_min')->nullable();
                }
                if (! Schema::hasColumn('produtos', 'controla_estoque')) {
                    $table->boolean('controla_estoque')->default(true);
                }
            });
        }

        if (! Schema::hasTable('estoque_movimentos')) {
            Schema::create('estoque_movimentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('produto_id')->nullable()->constrained('produtos')->cascadeOnDelete();
                $table->unsignedBigInteger('insumo_id')->nullable();
                $table->string('tipo', 32);
                $table->decimal('delta', 14, 3);
                $table->decimal('saldo_apos', 14, 3);
                $table->string('unidade', 8)->nullable();
                $table->nullableMorphs('referencia');
                $table->string('observacao', 500)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['empresa_id', 'created_at']);
                $table->index(['produto_id', 'created_at']);
                $table->index(['insumo_id', 'created_at']);
                $table->index(['tipo', 'created_at']);
            });
        } elseif (! Schema::hasColumn('estoque_movimentos', 'insumo_id')) {
            Schema::table('estoque_movimentos', function (Blueprint $table) {
                $table->unsignedBigInteger('insumo_id')->nullable()->after('produto_id');
                $table->string('unidade', 8)->nullable()->after('saldo_apos');
                $table->index(['insumo_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        // Não remove — é reparo idempotente.
    }
};
