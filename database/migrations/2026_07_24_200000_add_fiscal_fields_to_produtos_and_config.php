<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos fiscais no produto (opcionais) + padrões da empresa.
 * Regra: venda sempre funciona; fiscal só importa quando o módulo está ativo e emitindo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            if (! Schema::hasColumn('produtos', 'fiscal_tipo_item')) {
                $table->string('fiscal_tipo_item', 32)->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_herdar_padrao')) {
                $table->boolean('fiscal_herdar_padrao')->default(true);
            }
            if (! Schema::hasColumn('produtos', 'fiscal_ncm')) {
                $table->string('fiscal_ncm', 16)->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_cfop')) {
                $table->string('fiscal_cfop', 8)->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_origem')) {
                $table->unsignedTinyInteger('fiscal_origem')->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_unidade')) {
                $table->string('fiscal_unidade', 8)->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_csosn')) {
                $table->string('fiscal_csosn', 8)->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_cst')) {
                $table->string('fiscal_cst', 8)->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_cest')) {
                $table->string('fiscal_cest', 16)->nullable();
            }
            if (! Schema::hasColumn('produtos', 'fiscal_gtin')) {
                $table->string('fiscal_gtin', 20)->nullable();
            }
        });

        Schema::table('fiscal_configuracoes', function (Blueprint $table) {
            if (! Schema::hasColumn('fiscal_configuracoes', 'padrao_ncm')) {
                $table->string('padrao_ncm', 16)->nullable();
            }
            if (! Schema::hasColumn('fiscal_configuracoes', 'padrao_origem')) {
                $table->unsignedTinyInteger('padrao_origem')->default(0);
            }
            if (! Schema::hasColumn('fiscal_configuracoes', 'padrao_unidade')) {
                $table->string('padrao_unidade', 8)->default('UN');
            }
            if (! Schema::hasColumn('fiscal_configuracoes', 'padrao_csosn')) {
                $table->string('padrao_csosn', 8)->default('102');
            }
            if (! Schema::hasColumn('fiscal_configuracoes', 'padrao_cst')) {
                $table->string('padrao_cst', 8)->nullable();
            }
            if (! Schema::hasColumn('fiscal_configuracoes', 'padrao_cfop_producao')) {
                $table->string('padrao_cfop_producao', 8)->default('5101');
            }
            if (! Schema::hasColumn('fiscal_configuracoes', 'padrao_cfop_revenda')) {
                $table->string('padrao_cfop_revenda', 8)->default('5102');
            }
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            foreach ([
                'fiscal_tipo_item',
                'fiscal_herdar_padrao',
                'fiscal_ncm',
                'fiscal_cfop',
                'fiscal_origem',
                'fiscal_unidade',
                'fiscal_csosn',
                'fiscal_cst',
                'fiscal_cest',
                'fiscal_gtin',
            ] as $col) {
                if (Schema::hasColumn('produtos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('fiscal_configuracoes', function (Blueprint $table) {
            foreach ([
                'padrao_ncm',
                'padrao_origem',
                'padrao_unidade',
                'padrao_csosn',
                'padrao_cst',
                'padrao_cfop_producao',
                'padrao_cfop_revenda',
            ] as $col) {
                if (Schema::hasColumn('fiscal_configuracoes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
