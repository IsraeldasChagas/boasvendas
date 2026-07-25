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

## Insumos (ingredientes)

Tabela `insumos`: o que a cozinha consome (polpa, leite, copo). Cada insumo tem
uma **unidade base** definida no cadastro e imutável depois:

| Base | O que aceita informar | Conversão |
|------|-----------------------|-----------|
| `g` (peso) | g, kg | 1 kg = 1000 g |
| `ml` (volume) | ml, L | 1 L = 1000 ml |
| `un` (contagem) | un | — |

O saldo é **sempre guardado na unidade base**, então qualquer módulo lê o mesmo
número. A conversão fica em `App\Enums\Estoque\UnidadeMedida`; a exibição encolhe
para kg/L quando passa de 1000 (`UnidadeMedida::formatar`). Informar uma unidade
incompatível (kg em insumo de volume) é recusado na validação.

Entrada e ajuste só pelo `EstoqueService` (`reporInsumo` / `ajustarInsumo`), com
movimento gravado — o campo de saldo no cadastro é somente leitura.

## Ficha técnica = receita do prato

Tabela `produto_ficha_itens` liga o prato aos insumos. Cada linha guarda o que o
usuário digitou (`quantidade` + `unidade`, ex.: 0,3 kg) e o equivalente em
unidade base (`quantidade_base`, 300 g), que é o valor usado na baixa.

No produto: `modo_preparo` (texto livre para a cozinha), `ficha_rendimento`
(quantas porções a receita rende) e `ficha_tempo_preparo_min`.

Regras:

- Consumo por venda = `quantidade_base / ficha_rendimento`. Receita de 1 kg que
  rende 4 porções consome 250 g por porção vendida.
- Falta de insumo **não trava a venda**: consumo parcial (até zerar) e a
  observação registra o déficit.
- Cancelamento devolve o produto final **e** os insumos da receita.
- `Produto::porcoesPossiveisPelaFicha()` responde "quantos pratos ainda dá para
  fazer" pelo insumo mais escasso; `insumoLimitanteDaFicha()` diz qual é ele.

## UI

- **Menu Estoque** (`/empresa/estoque`): lista com saldo, situação
  (OK / Baixo ≤ 10 / Zerado / Sem controle) e filtro de estoque baixo.
- **Movimentar** (`/empresa/estoque/{produto}`): repor, ajustar (inventário) e
  histórico dos últimos 100 movimentos.
- **Ficha técnica** (`/empresa/estoque/{produto}/ficha-tecnica`): foto do prato,
  ingredientes com foto/quantidade/unidade, rendimento, tempo, modo de preparo e
  o aviso de quantas porções ainda dá para produzir.
- **Menu Insumos** (`/empresa/insumos`): CRUD com foto, unidade base, estoque
  mínimo, custo; tela de movimentos com entrada, ajuste e histórico.
- **Painel da cozinha** (`/empresa/mesas/cozinha`): cada item abre a ficha
  técnica (ingredientes com foto + modo de preparo) sem sair da fila.
- **Cadastro de produto**: estoque inicial só na criação; na edição o saldo é
  somente leitura com link para a tela de movimentação (mantém o histórico).
- **Relatórios**: bloco "Estoque baixo" linka para a tela de estoque.

## Tipos de movimento

`venda_pdv`, `venda_loja`, `venda_mesa`, `remessa_ve`, `acerto_ve`,
`cancelamento`, `ajuste`, `reposicao`, `consumo_ficha`.

`estoque_movimentos` serve produto **e** insumo: preenche `produto_id` ou
`insumo_id`, e nos movimentos de insumo grava a `unidade` base. `delta` e
`saldo_apos` são decimais (3 casas) para caber 12,5 g. Cada linha guarda também a
referência polimórfica (pedido, comanda, remessa, acerto), usuário e observação.

## Migrations

- `2026_07_24_210000_add_estoque_control_and_movimentos.php`
- `2026_07_24_211000_create_produto_ficha_itens_table.php` (insumos, ficha e
  campos de preparo no produto)

Rodar `php artisan migrate`. Sem as tabelas novas, o sistema segue funcionando
no comportamento antigo (guards por `Schema::hasTable`/`hasColumn`).

## Fora da v1

WMS, compras/fornecedores, multi-depósito, inventário cego e integração
SAS-Estoque como fonte de verdade (ver `docs/analise-integracao-api-sas-estoque.md`).
