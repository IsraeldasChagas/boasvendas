<?php

namespace App\Support\Fiscal;

use App\Enums\Fiscal\FiscalModoEmissao;
use App\Models\FiscalConfiguracao;
use App\Models\Produto;
use Illuminate\Support\Facades\Schema;

/**
 * Helpers fiscais do produto — regra progressiva (venda sempre; fiscal opcional).
 */
final class ProdutoFiscal
{
    public const TIPO_PRODUCAO_PROPRIA = 'producao_propria';

    public const TIPO_REVENDA = 'revenda';

    public const TIPO_INSUMO = 'insumo';

    /** @return array<string, string> */
    public static function tiposItemRotulos(): array
    {
        return [
            self::TIPO_PRODUCAO_PROPRIA => 'Produção própria (você faz)',
            self::TIPO_REVENDA => 'Revenda (comprou pronto)',
            self::TIPO_INSUMO => 'Insumo (não vende ao cliente)',
        ];
    }

    /** @return array<int, string> */
    public static function origensRotulos(): array
    {
        return [
            0 => '0 — Nacional',
            1 => '1 — Estrangeira (importação direta)',
            2 => '2 — Estrangeira (mercado interno)',
            3 => '3 — Nacional (conteúdo importação > 40%)',
            4 => '4 — Nacional (processos básicos)',
            5 => '5 — Nacional (conteúdo importação ≤ 40%)',
            6 => '6 — Estrangeira (importação direta, sem similar)',
            7 => '7 — Estrangeira (mercado interno, sem similar)',
            8 => '8 — Nacional (conteúdo importação > 70%)',
        ];
    }

    public static function empresaEmiteNota(?FiscalConfiguracao $config): bool
    {
        if ($config === null) {
            return false;
        }
        if (! $config->modulo_ativo) {
            return false;
        }

        $modo = $config->modo_emissao;
        if ($modo instanceof FiscalModoEmissao) {
            return $modo !== FiscalModoEmissao::NaoEmitir;
        }

        return (string) $modo !== FiscalModoEmissao::NaoEmitir->value;
    }

    public static function schemaTemCamposProduto(): bool
    {
        try {
            return Schema::hasColumn('produtos', 'fiscal_tipo_item');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Valores efetivos para emissão (produto + padrões da empresa).
     *
     * @return array{
     *   tipo_item: ?string,
     *   ncm: ?string,
     *   cfop: ?string,
     *   origem: ?int,
     *   unidade: ?string,
     *   csosn: ?string,
     *   cst: ?string,
     *   cest: ?string,
     *   gtin: ?string,
     * }
     */
    public static function resolverEfetivo(Produto $produto, ?FiscalConfiguracao $config = null): array
    {
        $config ??= $produto->empresa?->fiscalConfiguracao;
        $herdar = (bool) ($produto->fiscal_herdar_padrao ?? true);
        $tipo = $produto->fiscal_tipo_item;

        $cfopPadrao = null;
        if ($config !== null) {
            $cfopPadrao = match ($tipo) {
                self::TIPO_PRODUCAO_PROPRIA => $config->padrao_cfop_producao ?: '5101',
                self::TIPO_REVENDA => $config->padrao_cfop_revenda ?: '5102',
                default => $config->padrao_cfop_producao ?: '5101',
            };
        }

        $pick = function (?string $produtoVal, mixed $padrao) use ($herdar): ?string {
            $v = trim((string) ($produtoVal ?? ''));
            if ($v !== '') {
                return $v;
            }
            if (! $herdar) {
                return null;
            }
            $p = trim((string) ($padrao ?? ''));

            return $p !== '' ? $p : null;
        };

        $origemProduto = $produto->fiscal_origem;
        $origem = $origemProduto !== null && $origemProduto !== ''
            ? (int) $origemProduto
            : ($herdar && $config !== null ? (int) ($config->padrao_origem ?? 0) : null);

        return [
            'tipo_item' => $tipo,
            'ncm' => $pick($produto->fiscal_ncm ?? null, $config?->padrao_ncm),
            'cfop' => $pick($produto->fiscal_cfop ?? null, $cfopPadrao),
            'origem' => $origem,
            'unidade' => $pick($produto->fiscal_unidade ?? null, $config?->padrao_unidade ?: 'UN'),
            'csosn' => $pick($produto->fiscal_csosn ?? null, $config?->padrao_csosn ?: '102'),
            'cst' => $pick($produto->fiscal_cst ?? null, $config?->padrao_cst),
            'cest' => $pick($produto->fiscal_cest ?? null, null),
            'gtin' => $pick($produto->fiscal_gtin ?? null, null),
        ];
    }
}
