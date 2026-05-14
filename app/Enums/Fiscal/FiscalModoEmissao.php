<?php

namespace App\Enums\Fiscal;

enum FiscalModoEmissao: string
{
    case NaoEmitir = 'nao_emitir';
    case SobDemanda = 'sob_demanda';
    case Automatica = 'automatica';

    public function rotulo(): string
    {
        return match ($this) {
            self::NaoEmitir => 'Não emitir',
            self::SobDemanda => 'Emitir apenas quando solicitado',
            self::Automatica => 'Emitir automaticamente',
        };
    }
}
