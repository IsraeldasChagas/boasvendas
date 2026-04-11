<?php

namespace App\Support;

final class Cep
{
    /** CEP brasileiro com 8 dígitos (zero à esquerda), ou null se inválido. */
    public static function normalizar8(?string $cep): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $cep);
        if ($d === '') {
            return null;
        }
        if (strlen($d) > 8) {
            $d = substr($d, 0, 8);
        }
        if (strlen($d) < 8) {
            $d = str_pad($d, 8, '0', STR_PAD_LEFT);
        }

        return strlen($d) === 8 ? $d : null;
    }
}
