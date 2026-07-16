# Análise: preparação do Vendaffacil para API com SAS-Estoque

**Projeto:** Vendaffacil (`vendaffacil.com.br`)  
**Escopo:** somente análise e documentação — sem implementação.  
**Data da análise:** 2026-07-16  
**Princípios:**

- O Vendaffacil permanece sistema **independente**, **multiempresa** e vendável a outros clientes.
- O SAS-Estoque é apenas um **consumidor autorizado** da API.
- A integração **não** pode ficar presa a um cliente, unidade ou ID específico.
- Não copiar senhas, tokens, chaves ou segredos reais nesta documentação.

---

## Objetivo da arquitetura

| Domínio | Responsável principal |
|--------|------------------------|
| Clientes comerciais, catálogo de venda, preços, adicionais, pedidos, delivery, PDV, caixa, pagamentos, comprovantes, fiscal, histórico comercial | **Vendaffacil** (motor comercial/fiscal via API) |
| Estoque real, compras, fornecedores, perdas, inventário, unidades operacionais, fichas técnicas, consumo de insumos, gestão administrativa interna | **SAS-Estoque** |

O usuário poderá operar pelo SAS-Estoque; operações comerciais/fiscais serão enviadas, processadas ou consultadas no Vendaffacil pela API.

---

## 1. Arquitetura geral

### Stack

| Item | Valor |
|------|--------|
| PHP | `^8.2` |
| Laravel | `^12.0` (`composer.json`) |
| Nome do pacote | `laravel/laravel` — descrição: SaaS multiempresa |
| Auth API (Sanctum/Passport/JWT) | **Ausente** |
| Arquivo `routes/api.php` | **Ausente** |

Dependências relevantes: `laravel/framework`, `endroid/qr-code`. Sem pacotes de OAuth/API tokens.

### Estrutura de pastas (relevantes)

```text
app/
  Enums/Fiscal, Enums/Mesas
  Http/Controllers/{Admin,Auth,Empresa,Publico,Site,Api}
  Http/Middleware/
  Models/
  Providers/
  Services/{Fiscal,Mesas,...}
  Support/
bootstrap/app.php
config/{app,auth,vendaffacil,fiscal,services,queue,cache,...}
database/migrations, seeders
routes/web.php, routes/console.php
resources/views/
```

### Padrão arquitetural

- **MVC Laravel** com Controllers + Eloquent Models.
- **Services** pontuais (Fiscal, Mesas/Comanda, Delivery/Frete, Fidelidade OTP, Geocoding, Vitrine).
- **Não há** pastas/camadas de: Repositories, Actions, Jobs, Events, Listeners, Policies, Traits de domínio.
- Isolamento multiempresa na web via **middleware** + **`Route::bind`** em `AppServiceProvider` (filtra por `auth()->user()->empresa_id`).
- **Não há Global Scopes** nos models.

### Controllers (áreas)

| Área | Prefixo / papel |
|------|------------------|
| `Site\` | Marketing / planos |
| `Auth\` | Login sessão |
| `Admin\` | Painel master (empresas, planos, módulos, usuários, suporte) |
| `Empresa\` | Painel do tenant (pedidos, PDV, produtos, clientes, caixa, fiscal, mesas, etc.) |
| `Publico\` | Vitrine `/loja/{slug}`, carrinho, checkout, fidelidade, entregador |
| `Api\CalcularEntregaController` | Único endpoint JSON dedicado (`POST /api/calcular-entrega`, middleware `web`) |

### Middlewares (`bootstrap/app.php`)

| Alias | Classe | Função |
|-------|--------|--------|
| `admin` | `EnsureUserIsAdmin` | Painel `/admin` |
| `empresa.painel` | `EnsureEmpresaPainelAccess` | Exige `empresa_id`, empresa válida, não suspensa; master vai para `/admin` |
| `empresa.colaborador` | `EnsureEmpresaColaboradorPapel` | Restringe atendente / atendente_caixa |
| `empresa.menu` | `EnsureEmpresaMenuAccess` | Telas liberadas em `empresas.menu_acessos` |
| (grupo web) | `PreventStaleFormCache` | Cache de formulários |

### Autenticação e autorização (web)

- Guard padrão Laravel com **sessão** (`SESSION_DRIVER=database`).
- Login: `AuthController` + `throttle:10,1`.
- Sem Policies/Gates Spatie.
- Autorização: role do usuário + menu da empresa + checks manuais `empresa_id` nos controllers.

### Filas, jobs, events, cache, scheduler

| Recurso | Situação |
|---------|----------|
| Queue | `QUEUE_CONNECTION=database` (script `composer dev` sobe `queue:listen`) |
| Jobs / Events / Listeners | **Não implementados** no `app/` |
| Cache | `CACHE_STORE=database` |
| Scheduler | Sem schedule de domínio; comandos Artisan em `routes/console.php` (demo, Maps, fotos) |
| Logs | Canal `stack` / `LOG_LEVEL` via `.env` |
| Auditoria | Sem módulo de audit trail genérico; há `fiscal_logs` e histórico de fidelidade |

### Configuração e `.env`

Arquivos: `config/vendaffacil.php`, `config/fiscal.php`, `config/services.php`, etc.

Variáveis de interesse (nomes apenas — sem valores reais):

- `APP_*`, `DB_*`, `SESSION_*`, `QUEUE_*`, `CACHE_*`
- `VENDAFFACIL_SITE_NAME`, `VENDAFFACIL_ADMIN_EMAILS`, `VENDAFFACIL_TAXA_ENTREGA`
- `GOOGLE_MAPS_*`, `OSRM_*`, `NOMINATIM_*`, `OSM_HTTP_USER_AGENT`
- `FIDELIDADE_OTP_*` (webhook/Evolution — padrão semelhante a outros sistemas)

### Tratamento de erros e JSON

- Customização em `bootstrap/app.php`: `TokenMismatchException` → JSON 419 se `expectsJson()`, senão redirect com mensagem.
- **Não há** envelope JSON padronizado de API (`data` / `errors` / `meta`).
- Validação Laravel (`validate`) nas actions web; respostas majoritariamente Blade/redirect.

### Implicação para a API futura

Será necessário criar `routes/api.php` (ou grupo API), autenticação por token, envelope de resposta, rate limit e validação obrigatória de `empresa_id` (e futuramente `unidade_id`) em **todas** as queries — sem depender só dos `Route::bind` da sessão web.

---

## 2. Multiempresa e multiunidade

### Multiempresa — status: **verdadeiro SaaS**

| Elemento | Detalhe |
|----------|---------|
| Tabela | `empresas` |
| Model | `App\Models\Empresa` |
| Identificação | `id` interno; vitrine por `slug` (+ tabela `empresa_slugs`) |
| Separação de dados | FK `empresa_id` nos models de negócio |
| Sessão | Usuário autenticado carrega `empresa_id`; painel sob `/empresa/*` |
| Subdomínios | **Não** usados para tenant |
| Tokens de API | **Não** existem ainda |
| Status empresa | `ativa`, `trial`, `suspensa` |

Relacionamentos principais em `Empresa`: `users`, `produtos`, `categorias`, `adicionais`, `clientes`, `pedidos`, `caixaTurnos`, `fiscal*`, `mesas`, financeiro, venda externa, fidelidade, módulos, etc.

### Multiunidade — status: **reservado / incompleto**

| Elemento | Detalhe |
|----------|---------|
| Tabela `unidades` | **Não existe** |
| Model `Unidade` | **Não existe** |
| Campo `unidade_id` | Presente (nullable) em mesas, comandas, `mesa_configuracoes`, fiscal (config/emitente/nota/log) |
| Pedidos / produtos / clientes / caixa / PDV | **Sem** `unidade_id` |
| Filial na UI | Cosmético: `loja_filial_nome`, `loja_filial_logo` — não é entidade |

Migrations de mesas/fiscal documentam: `unidade_id` sem FK até existir cadastro de unidades.

### Isolamento e riscos de vazamento

| Controle atual | Limite |
|----------------|--------|
| `Route::bind` no painel filtra por `empresa_id` do user | Só vale com sessão autenticada web |
| Controllers fazem `abort(403)` se `pedido.empresa_id !== user.empresa_id` | Precisa ser replicado na API |
| Sem Global Scope | Query esquecida sem `where empresa_id` vaza dados |
| Loja pública por slug | Intencional (cardápio público); não é auth de integração |

### Pontos em que a API **deve** validar empresa (e unidade)

Obrigatório em todo endpoint autenticado:

1. Resolver tenant do token → `empresa_id`.
2. Garantir que o recurso pertence a essa empresa.
3. Quando multiunidade estiver ativa: validar `unidade_id` do contexto vs recurso (hoje só parcial em mesas/fiscal).
4. Nunca aceitar `empresa_id` do body sem checar contra o token.

### Hardcodes / amarras a cliente

- Placeholder de exemplo na view de configurações (“Sabor Paraense — Centro”) — **somente UI**, não regra.
- E-mails demo master (`master@vendaffacil.com.br`, `admin@vendaffacil.com.br`) e empresa demo (`slug` demo) — ambiente de demonstração do produto.
- Comentários/exemplo de hospedagem no `.env.example` — não amarram a API a um cliente.

**Conclusão:** o produto é multiempresa; a integração SAS deve ser genérica por token + `empresa_id`, sem nomes/IDs fixos.

---

## 3. Usuários, perfis e permissões

### Modelo atual

| Conceito | Implementação |
|----------|----------------|
| Usuários | `users` + `App\Models\User` |
| Vínculo | `empresa_id` (nullable para master) |
| Papéis equipe | coluna `role` (string) |
| Admin master | lista de e-mails em `config('vendaffacil.admin_emails')` |
| Policies / Gates / Spatie | **Ausentes** |
| Menus | `empresas.menu_acessos` (array de keys) + middlewares |

### Roles de equipe (`User`)

| Constante | Valor | Uso típico |
|-----------|-------|------------|
| `ROLE_GESTOR` | `gestor` | Acesso completo ao painel |
| `ROLE_OPERADOR` | `operador` | Operador de caixa / painel amplo |
| `ROLE_ENTREGADOR` | `entregador` | Painel amplo (mesmo critério “completo” em vários métodos) |
| `ROLE_ATENDENTE` | `atendente` | Só mesas/comandas/cardápio (restrito) |
| `ROLE_ATENDENTE_CAIXA` | `atendente_caixa` | Mesas + pedidos + PDV + caixa |

Restrição de rotas: `User::podeAcessarRotaEmpresa()`.

### Usuário de API

Não existe. Integrações futuras não devem reutilizar senha de gestor web; devem usar **token de aplicação** por empresa.

### Como criar permissões futuras

Sugestão compatível com o projeto atual:

1. **Scopes/abilities no token de API** (preferencial para SAS e outros consumidores).
2. **Telas/módulo no painel** para humanos gerenciarem tokens (`menu_acessos` + keys `api.*`).
3. Opcional futuro: Spatie Permission — só se unificar web + API; hoje o app é role-based simples.

#### Catálogo sugerido de abilities

```text
api.visualizar
api.configurar
api.tokens.criar
api.tokens.revogar
api.logs.visualizar
api.webhooks.configurar
pedidos.api.criar
pedidos.api.consultar
pedidos.api.cancelar
vendas.api.consultar
caixa.api.consultar
fiscal.api.emitir
fiscal.api.cancelar
```

Estender depois para: `produtos.api.*`, `clientes.api.*`, `catalogo.api.*`, etc.

---

## 4. Autenticação da API (estado e recomendação)

### O que já existe

| Mecanismo | Situação |
|-----------|----------|
| Laravel Sanctum | Não |
| Laravel Passport | Não |
| JWT | Não |
| API keys próprias | Não |
| OAuth | Não |
| Bearer de integração | Não |
| Rate limiting | Sim (`throttle:*` em rotas web) |
| Token fiscal de terceiros | `fiscal_emitentes.api_token` (encrypted) — credencial do **emissor**, não auth Vendaffacil |

### Recomendação (compatível e multi-cliente)

Adotar **Laravel Sanctum (Personal Access Tokens)** ou tabela espelho `empresa_api_tokens` com o mesmo espírito:

| Requisito futuro | Como atender |
|------------------|--------------|
| Token por cliente (empresa) | `empresa_id` no token |
| Token por integração | nome/label (ex.: `sas-estoque-prod`) |
| Permissões por token | `abilities` / scopes |
| Revogação | soft-delete / `revoked_at` |
| Expiração opcional | `expires_at` nullable |
| Identificação empresa | obrigatória no token |
| Identificação unidade | opcional (`unidade_id` nullable) |
| Logs de uso | tabela `api_request_logs` ou canal dedicado |
| Bloqueio por IP | `allowed_ips` JSON opcional |
| Homologação / produção | tokens distintos + `APP_ENV` / flag `environment` |

O SAS-Estoque autentica com `Authorization: Bearer {token}` e opera **somente** no tenant do token. Outros clientes Vendaffacil usam o mesmo mecanismo com seus próprios tokens.

---

## 5. Produtos, categorias e catálogo

### Modelos

| Model | Tabela | Escopo |
|-------|--------|--------|
| `Categoria` | `categorias` | `empresa_id` |
| `Produto` | `produtos` | `empresa_id`, unique `(empresa_id, sku)` |
| `Adicional` | `adicionais` | `empresa_id` + pivot `adicional_produto` |
| `ProdutoIngrediente` | `produto_ingredientes` | por produto |

### Campos e capacidades

| Tema | Situação no Vendaffacil |
|------|-------------------------|
| Categorias | Sim (`nome`, `ordem`, `ativo`) |
| Subcategorias | **Não** |
| Preço de venda | `produtos.preco` |
| Preço promocional | **Não** |
| Custo | **Não** |
| Unidade de medida | **Não** |
| Variações / tamanhos | **Não** |
| Adicionais / complementos | Sim (`acrescentar` / `retirar`) |
| Grupos de adicionais | Limitado (min/max escolhas no produto; sem entidade “grupo”) |
| Remoção de ingredientes | Sim (`produto_ingredientes` + UI) |
| Observações | No pedido/item (`observacoes`, `opcoes_linha`) |
| Imagens | `foto` (produto/adicional) via uploads |
| Código interno | `sku` |
| Código de barras | **Não** |
| Ativo / inativo | `ativo` |
| Visível na loja | `visivel_loja` |
| Por empresa | Sim |
| Por unidade | **Não** |
| Disponibilidade por canal | Indireto (`visivel_loja` + canais de pedido) |
| Disponibilidade por horário | **Não** |
| Campos fiscais no produto | **Não** (fiscal no pedido/nota/emitente) |
| Composição / ficha técnica | **Não** (papel do SAS) |
| Estoque | `estoque` inteiro simples; decremento/restauração em pedidos |
| Controle disponibilidade | `ativo` + `visivel_loja` + estoque opcional |

### Sync sugerido

| Sincronizar / expor na API Vendaffacil | Permanecer no SAS-Estoque |
|----------------------------------------|---------------------------|
| SKU, nome comercial, categoria, preço venda, adicionais, ativo/visível, foto URL, limites de personalização | Saldo WMS, compras, fornecedores, perdas, inventário, fichas técnicas, consumo de insumos, custo de insumos |
| Eventos de venda/pedido (para baixa lógica no SAS) | Fonte da verdade de estoque físico |

O campo `estoque` do Vendaffacil deve ser tratado como **disponibilidade comercial**, não como estoque gerencial.

---

## 6. Clientes

### Cadastro atual (`clientes`)

| Campo | Existe |
|-------|--------|
| `empresa_id` | Sim |
| `nome` | Sim |
| `email` | Sim (nullable) |
| `telefone` | Sim (nullable) |
| `documento` | Sim (string genérica CPF/CNPJ) |
| `observacoes` | Sim |
| `ativo` | Sim |
| PF/PJ tipado | Não |
| WhatsApp separado | Não |
| Endereço estruturado (CEP, nº, bairro, cidade, UF, referência) | Não no cadastro; endereço vai no **pedido** |
| LGPD / consentimento | Não |
| Bloqueio / limite de crédito | Não |
| Cliente por unidade | Não |
| FK pedido → cliente | Não — pedido guarda snapshot (`cliente_nome`, telefone, e-mail, endereço) |
| Histórico | Via pedidos da empresa (por telefone/documento) + fidelidade (`fidelidade_cartoes`) |

### Ownership recomendado

- **Registro principal comercial/fiscal:** Vendaffacil (emissão de nota, histórico de pedidos/vendas).
- **Sync bidirecional seletivo** com SAS: match por `documento` + telefone; enriquecer endereço no VF quando o SAS enviar checkout/pedido.
- Evitar dois “mestres” conflitantes: SAS pode espelhar, mas regras fiscais/comerciais ficam no VF.

---

## 7. Pedidos

### Criação e canais

| Aspecto | Detalhe |
|---------|---------|
| Vitrine | Carrinho em sessão → checkout público `/loja/{slug}` |
| PDV | `PdvController` — canais `balcao` / `whatsapp` |
| Mesa | Módulo `comandas` (fluxo separado de `pedidos`) |
| Model | `Pedido` + `PedidoItem` |

### Campos principais do pedido

`empresa_id`, `codigo_publico`, `canal`, `tipo_entrega`, dados do cliente (snapshot), `endereco`, `complemento`, `cep_entrega`, `forma_pagamento`, `pagamento_troco_para`, `observacoes`, campos fiscais do consumidor, `status`, `entregador_token`, `subtotal`, `taxa_entrega`, `total`.

Itens: `produto_id`, `nome_produto`, `preco_unitario`, `quantidade`, `subtotal`, `opcoes_linha` (JSON — adicionais/opções).

### Canais (`Pedido`)

```text
loja       → Vitrine online
balcao     → PDV balcão
whatsapp   → PDV WhatsApp/telefone
```

### Tipos de entrega

```text
entrega
balcao     → Retirada no balcão
```

### Formas de pagamento (pedido)

```text
pix
cartao_credito    → maquininha
cartao_debito     → maquininha
dinheiro
entrega           → na entrega (combinar)
cartao            → legado / genérico
```

### Status de pedido (valores reais)

```text
pendente_loja              Aguardando confirmação (quando loja_confirmar_pedidos)
recebido                   Recebido
preparo                    Em preparo
pronto                     Pronto
rota                       Em rota
entregue                   Entregue
cancelado                  Cancelado
endereco_nao_encontrado    Endereço não encontrado
```

### Transições

**Não há state machine formal** em `Pedido`. O painel (`PedidoController::updateStatus`) aceita qualquer status de `statusRotulos()`, exceto se o pedido ainda está `pendente_loja` (obrigatório Aceitar/Recusar).

Fluxos observados:

```text
pendente_loja  --aceitar-->  recebido
pendente_loja  --recusar-->  cancelado  (+ restaura estoque)

recebido / preparo / pronto / rota / ...
  --updateStatus-->  qualquer status da lista (sem validação de grafo)

pronto|rota  --link entregador-->  entregue | cancelado | endereco_nao_encontrado
```

Fluxo operacional **recomendado** (para a API documentar e, no futuro, reforçar):

```text
[pendente_loja?]
    → recebido
    → preparo
    → pronto
        → (entrega) rota → entregue
        → (balcão) entregue
    ↘ cancelado
    ↘ endereco_nao_encontrado
```

### Status fiscal (resumo no pedido)

```text
sem_nota
aguardando_emissao
nota_autorizada
nota_rejeitada
```

Detalhe em `FiscalNota` / enum `FiscalNotaStatus`:

```text
nao_emitida
aguardando_emissao
processando
autorizada
rejeitada
cancelada
contingencia
```

### Comandas (mesa) — domínio paralelo

**Comanda** (`ComandaStatus`):

```text
aberta → em_consumo → conta_solicitada → fechada
                                      ↘ cancelada
```

**Item de comanda** (`ComandaItemStatus`) — com validação de transição em `CozinhaService`:

```text
pendente → enviado → recebido → em_preparo → pronto → entregue
         ↘ cancelado (conforme fluxos de remoção)
```

Transições de cozinha permitidas (resumo):

```text
enviado     → recebido | em_preparo
recebido    → em_preparo
em_preparo  → pronto
pronto      → entregue
```

### Impressão, histórico, auditoria

- Impressão: rota `empresa.pedidos.imprimir` + flag `loja_impressao_pedido_habilitada`.
- Histórico: timestamps + mudanças de status (sem tabela de audit events dedicada para pedidos).
- WhatsApp: geração de link `wa.me` ao mudar status (`WhatsAppPedidoCliente`).

### Pagamentos vinculados

- Pedido: forma única no cabeçalho (não split nativo).
- Mesa: `PagamentoComanda` + statuses próprios (`confirmado` / `cancelado`).

---

## 8. Mapa de responsabilidades (API futura)

Domínios que a API do Vendaffacil deverá expor (conforme objetivo):

| Domínio | Base atual no código |
|---------|----------------------|
| Clientes | `ClienteController`, model `Cliente` |
| Produtos / categorias / preços / adicionais | Controllers + models correspondentes |
| Pedidos / delivery | `PedidoController`, `PublicoController`, `DeliveryFreteService` |
| PDV | `PdvController` |
| Caixa | `CaixaController`, `CaixaTurno`, `CaixaMovimento` |
| Formas de pagamento | constantes em `Pedido` (+ caixa/comanda) |
| Comprovantes / impressão | views/rotas de imprimir |
| Fiscal | `FiscalService`, drivers, `PedidoFiscalController` |
| Status / documentos fiscais | `FiscalNota`, `FiscalLog` |
| Histórico comercial | pedidos, financeiro, relatórios |
| Mesas / comandas | `MesaService`, `ComandaService`, `CozinhaService` |

Fora do escopo da API comercial (permanecem no SAS): estoque WMS, compras, fornecedores, perdas, inventário, fichas técnicas, insumos.

---

## 9. Riscos e gaps para a integração

1. **Sem camada API REST** — precisa ser criada do zero (rotas, auth, contratos).
2. **Sem token por empresa** — requisito bloqueante para SAS e outros clientes.
3. **Sem Global Scope** — risco alto se a API não filtrar tenant em toda query.
4. **Multiunidade incompleta** — API deve nascer com `unidade_id` opcional no contrato.
5. **Estoque fraco no VF** — não usar como WMS; integrar eventos de venda para o SAS baixar insumos.
6. **Cliente sem endereço/LGPD** — pode exigir evolução de schema antes de sync rico.
7. **Status de pedido sem máquina de estados** — a API deve documentar e preferencialmente validar transições.
8. **Permissões granulares inexistentes** — implementar via abilities do token + telas de gestão.

---

## 10. Princípios de desenho (obrigatórios)

1. Vendaffacil continua vendável a qualquer empresa-cliente.
2. SAS-Estoque = um app cliente da API, identificado só por token + `empresa_id` (e `unidade_id` quando houver).
3. Proibido: IFs com nome de empresa, CNPJ fixo, slug fixo de um grupo, IDs hardcoded de unidade.
4. Segredos só em `.env` / secrets do servidor — nunca em código, seeds versionados com valor real, ou neste documento.
5. Homologação e produção com tokens e bases/ambientes separados.

---

## 11. Próximos documentos sugeridos (ainda sem implementar)

1. Contrato OpenAPI preliminar (recursos, verbos, códigos HTTP).
2. Matriz campo a campo VF ↔ SAS (produtos, clientes, pedidos, baixa de estoque).
3. Desenho de webhooks (pedido criado/atualizado, nota autorizada/cancelada).
4. Plano de multiunidade (tabela `unidades` + migração gradual de `unidade_id`).
5. Spec de abilities e UI de gestão de tokens no painel empresa.

---

*Documento gerado a partir de análise estática do código em `C:\vendaffacil`. Nenhuma alteração de comportamento foi feita nesta etapa.*
