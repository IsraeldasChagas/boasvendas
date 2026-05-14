<?php

namespace App\Enums\Fiscal;

enum FiscalNotaStatus: string
{
    case NaoEmitida = 'nao_emitida';
    case AguardandoEmissao = 'aguardando_emissao';
    case Processando = 'processando';
    case Autorizada = 'autorizada';
    case Rejeitada = 'rejeitada';
    case Cancelada = 'cancelada';
    case Contingencia = 'contingencia';

    public function rotulo(): string
    {
        return match ($this) {
            self::NaoEmitida => 'Não emitida',
            self::AguardandoEmissao => 'Aguardando emissão',
            self::Processando => 'Processando',
            self::Autorizada => 'Autorizada',
            self::Rejeitada => 'Rejeitada',
            self::Cancelada => 'Cancelada',
            self::Contingencia => 'Contingência',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::Autorizada => 'bg-success-subtle text-success',
            self::Rejeitada => 'bg-danger-subtle text-danger',
            self::Cancelada => 'bg-secondary-subtle text-secondary',
            self::Processando, self::AguardandoEmissao => 'bg-warning-subtle text-warning-emphasis',
            self::Contingencia => 'bg-info-subtle text-info',
            self::NaoEmitida => 'bg-light text-muted',
        };
    }

    /** @return array<string, string> */
    public static function rotulos(): array
    {
        $out = [];
        foreach (self::cases() as $c) {
            $out[$c->value] = $c->rotulo();
        }

        return $out;
    }
}
