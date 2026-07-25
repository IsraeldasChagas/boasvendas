<?php

namespace App\Enums\Estoque;

enum EstoqueMovimentoTipo: string
{
    case VendaPdv = 'venda_pdv';
    case VendaLoja = 'venda_loja';
    case VendaMesa = 'venda_mesa';
    case RemessaVe = 'remessa_ve';
    case AcertoVe = 'acerto_ve';
    case Cancelamento = 'cancelamento';
    case Ajuste = 'ajuste';
    case Reposicao = 'reposicao';
    case ConsumoFicha = 'consumo_ficha';

    public function rotulo(): string
    {
        return match ($this) {
            self::VendaPdv => 'Venda PDV',
            self::VendaLoja => 'Venda loja',
            self::VendaMesa => 'Venda mesa',
            self::RemessaVe => 'Remessa (venda externa)',
            self::AcertoVe => 'Acerto (venda externa)',
            self::Cancelamento => 'Cancelamento / devolução',
            self::Ajuste => 'Ajuste manual',
            self::Reposicao => 'Reposição',
            self::ConsumoFicha => 'Consumo de insumo (ficha técnica)',
        };
    }
}
