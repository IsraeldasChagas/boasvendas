<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->unsignedSmallInteger('acrescimo_escolhas_min')->nullable()->after('permite_adicionais');
            $table->unsignedSmallInteger('acrescimo_escolhas_max')->nullable()->after('acrescimo_escolhas_min');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn(['acrescimo_escolhas_min', 'acrescimo_escolhas_max']);
        });
    }
};
