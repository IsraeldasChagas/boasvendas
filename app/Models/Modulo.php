<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'modulos';

    protected $fillable = [
        'nome',
        'categoria',
        'situacao',
        'ordem',
    ];

    protected function casts(): array
    {
        return [
            'ordem' => 'integer',
        ];
    }

    public static function situacoes(): array
    {
        return [
            'ativo' => 'Ativo',
            'addon' => 'Add-on',
            'roadmap' => 'Roadmap',
        ];
    }
}
