<?php

namespace App\Enums\Fiscal;

enum FiscalLogTipo: string
{
    case Emissao = 'emissao';
    case Cancelamento = 'cancelamento';
    case Consulta = 'consulta';
    case Erro = 'erro';
    case Excecao = 'excecao';
    case Webhook = 'webhook';
    case Config = 'config';

    public function rotulo(): string
    {
        return match ($this) {
            self::Emissao => 'Emissão',
            self::Cancelamento => 'Cancelamento',
            self::Consulta => 'Consulta',
            self::Erro => 'Erro',
            self::Excecao => 'Exceção',
            self::Webhook => 'Webhook',
            self::Config => 'Configuração',
        };
    }
}
