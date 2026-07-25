<?php

namespace App\Enums\Estoque;

/**
 * Unidades aceitas na ficha técnica. Todo saldo é guardado na unidade base
 * (g para peso, ml para volume, un para contagem) para que qualquer módulo
 * leia o mesmo número sem ambiguidade.
 */
enum UnidadeMedida: string
{
    case Grama = 'g';
    case Quilo = 'kg';
    case Mililitro = 'ml';
    case Litro = 'l';
    case Unidade = 'un';

    public function rotulo(): string
    {
        return match ($this) {
            self::Grama => 'g (gramas)',
            self::Quilo => 'kg (quilos)',
            self::Mililitro => 'ml (mililitros)',
            self::Litro => 'L (litros)',
            self::Unidade => 'un (unidades)',
        };
    }

    public function sigla(): string
    {
        return match ($this) {
            self::Litro => 'L',
            default => $this->value,
        };
    }

    /** Unidade em que o saldo é armazenado. */
    public function base(): self
    {
        return match ($this) {
            self::Grama, self::Quilo => self::Grama,
            self::Mililitro, self::Litro => self::Mililitro,
            self::Unidade => self::Unidade,
        };
    }

    /** Quantas unidades base cabem em 1 desta unidade. */
    public function fatorParaBase(): float
    {
        return match ($this) {
            self::Quilo, self::Litro => 1000.0,
            default => 1.0,
        };
    }

    public function paraBase(float $quantidade): float
    {
        return round($quantidade * $this->fatorParaBase(), 3);
    }

    public function daBase(float $quantidadeBase): float
    {
        return round($quantidadeBase / $this->fatorParaBase(), 3);
    }

    /** Unidades que podem ser informadas para um insumo desta base. */
    public function compativeis(): array
    {
        return match ($this->base()) {
            self::Grama => [self::Grama, self::Quilo],
            self::Mililitro => [self::Mililitro, self::Litro],
            self::Unidade => [self::Unidade],
        };
    }

    public function compativelCom(self $outra): bool
    {
        return $this->base() === $outra->base();
    }

    /** Apenas as bases válidas para cadastro de insumo. */
    public static function basesDisponiveis(): array
    {
        return [self::Grama, self::Mililitro, self::Unidade];
    }

    /** Formata para leitura humana, encolhendo g→kg e ml→L quando grande. */
    public static function formatar(float $quantidadeBase, self $base): string
    {
        $abs = abs($quantidadeBase);

        if ($base === self::Grama && $abs >= 1000) {
            return self::numero($quantidadeBase / 1000).' kg';
        }
        if ($base === self::Mililitro && $abs >= 1000) {
            return self::numero($quantidadeBase / 1000).' L';
        }

        return self::numero($quantidadeBase).' '.$base->sigla();
    }

    private static function numero(float $valor): string
    {
        $arredondado = round($valor, 3);

        return rtrim(rtrim(number_format($arredondado, 3, ',', '.'), '0'), ',');
    }
}
