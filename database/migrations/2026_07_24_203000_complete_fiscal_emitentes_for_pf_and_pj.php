<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_empresas', function (Blueprint $table) {
            $table->string('tipo_pessoa', 2)->default('pj')->after('empresa_id');
            $table->string('cnpj', 18)->nullable()->change();
            $table->string('cpf', 14)->nullable()->after('cnpj');
            $table->string('indicador_ie', 24)->nullable()->after('inscricao_estadual');
            $table->string('inscricao_municipal', 32)->nullable()->after('indicador_ie');

            $table->string('cep', 9)->nullable()->after('regime_tributario');
            $table->string('logradouro', 180)->nullable()->after('cep');
            $table->string('numero', 20)->nullable()->after('logradouro');
            $table->string('complemento', 80)->nullable()->after('numero');
            $table->string('bairro', 80)->nullable()->after('complemento');
            $table->string('municipio', 100)->nullable()->after('bairro');
            $table->string('codigo_municipio_ibge', 7)->nullable()->after('municipio');
            $table->char('uf', 2)->nullable()->after('codigo_municipio_ibge');
            $table->string('telefone', 20)->nullable()->after('uf');
            $table->string('email_fiscal', 180)->nullable()->after('telefone');

            $table->string('serie_nfce', 8)->nullable()->after('csc_id');
            $table->unsignedBigInteger('proximo_numero_nfce')->default(1)->after('serie_nfce');
            $table->string('serie_nfe', 8)->nullable()->after('proximo_numero_nfce');
            $table->unsignedBigInteger('proximo_numero_nfe')->default(1)->after('serie_nfe');
            $table->string('serie_nfse', 8)->nullable()->after('proximo_numero_nfe');
            $table->unsignedBigInteger('proximo_numero_nfse')->default(1)->after('serie_nfse');

            $table->index(['empresa_id', 'tipo_pessoa']);
        });

        DB::table('fiscal_empresas')
            ->whereNotNull('regime_tributario')
            ->orderBy('id')
            ->eachById(function (object $emitente): void {
                $regime = mb_strtolower(trim((string) $emitente->regime_tributario));
                $crt = match (true) {
                    in_array($regime, ['1', 'simples', 'simples nacional'], true) => '1',
                    in_array($regime, ['2', 'simples excesso', 'simples excesso sublimite'], true) => '2',
                    in_array($regime, ['3', 'normal', 'regime normal'], true) => '3',
                    in_array($regime, ['4', 'mei'], true) => '4',
                    default => null,
                };

                DB::table('fiscal_empresas')->where('id', $emitente->id)->update([
                    'regime_tributario' => $crt,
                ]);
            });
    }

    public function down(): void
    {
        DB::table('fiscal_empresas')->whereNull('cnpj')->update(['cnpj' => '']);

        Schema::table('fiscal_empresas', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'tipo_pessoa']);
            $table->dropColumn([
                'tipo_pessoa', 'cpf', 'indicador_ie', 'inscricao_municipal',
                'cep', 'logradouro', 'numero', 'complemento', 'bairro',
                'municipio', 'codigo_municipio_ibge', 'uf', 'telefone',
                'email_fiscal', 'serie_nfce', 'proximo_numero_nfce',
                'serie_nfe', 'proximo_numero_nfe', 'serie_nfse',
                'proximo_numero_nfse',
            ]);
            $table->string('cnpj', 18)->nullable(false)->change();
        });
    }
};
