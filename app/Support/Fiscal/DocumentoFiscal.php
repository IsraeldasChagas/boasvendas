<?php

namespace App\Support\Fiscal;

final class DocumentoFiscal
{
    public static function somenteDigitos(?string $valor): string
    {
        return preg_replace('/\D+/', '', (string) $valor) ?? '';
    }

    public static function cpfValido(?string $valor): bool
    {
        $cpf = self::somenteDigitos($valor);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao <= 10; $posicao++) {
            $soma = 0;
            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }
            $digito = (10 * $soma) % 11;
            $digito = $digito === 10 ? 0 : $digito;
            if ($digito !== (int) $cpf[$posicao]) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(?string $valor): bool
    {
        $cnpj = self::somenteDigitos($valor);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], 13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $posicao => $pesos) {
            $soma = 0;
            foreach ($pesos as $i => $peso) {
                $soma += (int) $cnpj[$i] * $peso;
            }
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            if ($digito !== (int) $cnpj[$posicao]) {
                return false;
            }
        }

        return true;
    }

    public static function mascarar(?string $valor): string
    {
        $documento = self::somenteDigitos($valor);

        return match (strlen($documento)) {
            11 => substr($documento, 0, 3).'.***.***-'.substr($documento, -2),
            14 => substr($documento, 0, 2).'.***.***/****-'.substr($documento, -2),
            default => (string) $valor,
        };
    }
}
