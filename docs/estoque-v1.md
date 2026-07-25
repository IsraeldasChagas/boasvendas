# Controle de estoque v1 (completo e simples)

## Conceito

`produtos.estoque` é o **saldo comercial disponível**. Toda mutação passa pelo
`App\Services\Estoque\EstoqueService` (lock de linha + movimento gravado em
`estoque_movimentos`). Nunca usar `increment`/`decrement` direto.

## O que baixa / devolve automaticamente

| Canal | Baixa | Devolve |
|-------|-------|---------|
| PDV (`PdvController`) | Ao finalizar a venda | — |
| Loja online (`PublicoController`) | No checkout | Pedido recusado (loja) e cancelado pelo entregador |
| Mesas (`PagamentoComandaService`) | No fechamento da comanda (baixa parcial se faltar saldo — não trava o fechamento) | — |
| Venda externa (`VendaExternaController`) | Ao criar remessa (bloqueia se não houver saldo); edição ajusta a diferença | Acerto concluído devolve o não vendido; exclusão de remessa não acertada devolve tudo |

## Flag `controla_estoque`

Produto com `controla_estoque = false` vende sempre, sem checagem nem baixa
(ex.: serviços, "taxa" cadastrada como produto). Default `true`.

## Ficha técnica (insumos)

Tabela `produto_ficha_itens`: cada venda de 1 unidade do produto final também
baixa `quantidade` de cada insumo (outro produto). Regras:

- A falta de insumo **não trava a venda**: a baixa é parcial (até zerar) e a
  observação registra o déficit.
- Cancelamento devolve o produto final **e** os insumos.
- Gestão na tela Estoque → Movimentar → seção "Ficha técnica".

## UI

- **Menu Estoque** (`/empresa/estoque`): lista com saldo, situação
  (OK / Baixo ≤ 10 / Zerado / Sem controle) e filtro de estoque baixo.
- **Movimentar** (`/empresa/estoque/{produto}`): repor, ajustar (inventário),
  ficha técnica e histórico dos últimos 100 movimentos.
- **Cadastro de produto**: estoque inicial só na criação; na edição o saldo é
  somente leitura com link para a tela de movimentação (mantém o histórico).
- **Relatórios**: bloco "Estoque baixo" linka para a tela de estoque.

## Tipos de movimento

`venda_pdv`, `venda_loja`, `venda_mesa`, `remessa_ve`, `acerto_ve`,
`cancelamento`, `ajuste`, `reposicao`, `consumo_ficha`.
Cada linha grava `delta` (com sinal), `saldo_apos`, referência polimórfica
(pedido, comanda, remessa, acerto), usuário e observação.

## Migrations

- `2026_07_24_210000_add_estoque_control_and_movimentos.php`
- `2026_07_24_211000_create_produto_ficha_itens_table.php`

Rodar `php artisan migrate`. Sem as tabelas novas, o sistema segue funcionando
no comportamento antigo (guards por `Schema::hasTable`/`hasColumn`).

## Fora da v1

WMS, compras/fornecedores, multi-depósito, inventário cego e integração
SAS-Estoque como fonte de verdade (ver `docs/analise-integracao-api-sas-estoque.md`).
