<?php

namespace App\Enums\Fiscal;

enum FiscalTipoPessoa: string
{
    case PessoaJuridica = 'pj';
    case PessoaFisica = 'pf';

    public function rotulo(): string
    {
        return match ($this) {
            self::PessoaJuridica => 'Pessoa jurídica (CNPJ)',
            self::PessoaFisica => 'Pessoa física (CPF)',
        };
    }
}
