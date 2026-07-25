# Regra fiscal no produto (progressiva)

**Princípio:** venda sempre funciona. Fiscal é opcional.

## Quando aparece no cadastro de produto

A seção **Fiscal (nota)** só aparece se:

1. O módulo fiscal da empresa está **habilitado**, e  
2. O modo de emissão **não** é `nao_emitir`.

Caso contrário, o formulário de produto fica só comercial (nome, preço, estoque…).

## O que o vendedor preenche

1. **Tipo do produto:** produção própria / revenda / insumo  
2. **Usar padrões da empresa** (ligado por padrão)

Opcional — bloco **Avançado:** NCM, CFOP, origem, unidade, CSOSN/CST, CEST, GTIN.

## Padrões da empresa

Em **Fiscal → Configurações**: NCM, origem, unidade, CSOSN, CFOP produção (5101), CFOP revenda (5102).

Na emissão futura, `App\Support\Fiscal\ProdutoFiscal::resolverEfetivo()` mescla produto + padrões.
