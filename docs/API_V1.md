# API Vendaffacil v1

Documentação da **Fase 1** — infraestrutura de autenticação e status.  
Endpoints de produtos, clientes, pedidos, caixa e fiscal **ainda não existem**.

Base URL: `{APP_URL}/api/v1`

---

## Versionamento

- Todas as rotas REST ficam sob `/api/v1/...`
- Versão pública atual: `1.0` (`config/api.php` → `api.version`)
- Header de resposta: `X-Api-Version: 1.0`

Evoluções incompatíveis deverão usar `/api/v2`.

---

## Autenticação (Bearer Token)

A API **não** usa login por sessão nem cookies.

1. Cada empresa (tenant) pode ter **vários tokens**.
2. O token em texto puro é exibido **somente na criação** (nunca gravado no banco).
3. No banco: apenas `token_hash` (SHA-256), `token_prefix` (para identificação visual) e metadados.

### Header

```http
Authorization: Bearer vf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Accept: application/json
```

### Campos do token (`empresa_api_tokens`)

| Campo | Descrição |
|-------|-----------|
| `empresa_id` | Tenant dono do token |
| `nome` | Nome da integração (ex.: “Integração estoque homolog”) |
| `token_hash` | Hash do segredo |
| `abilities` | Lista de permissões (JSON) |
| `environment` | `homologacao` ou `producao` |
| `expires_at` | Opcional |
| `last_used_at` | Atualizado a cada uso válido |
| `revoked_at` | Preenchido na revogação |
| `allowed_ips` | Lista opcional de IPs permitidos |

### Abilities (Fase 1)

Mínima para o endpoint de status: `api.visualizar`

Catálogo completo em `config/api.php` (`api.abilities`). Abilities de pedidos/caixa/fiscal estão **reservadas** para fases futuras.

---

## Endpoint de teste

### `GET /api/v1/integration/status`

Valida conexão (ex.: SAS-Estoque ou qualquer consumidor autorizado).

**Ability:** `api.visualizar`

**Resposta 200:**

```json
{
  "success": true,
  "system": "VendaFácil",
  "api_version": "1.0",
  "laravel": "12.x.x",
  "company": {
    "id": "1",
    "name": "Nome da empresa"
  },
  "environment": "homologacao",
  "timestamp": "2026-07-16T15:00:00-03:00"
}
```

`company` vem **sempre** do token autenticado — não é possível consultar outra empresa.

---

## Padrão JSON

### Sucesso

```json
{
  "success": true,
  "...": "campos do recurso"
}
```

Meta opcional:

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

### Erro

```json
{
  "success": false,
  "message": "Descrição legível",
  "code": "api.invalid_token",
  "errors": {}
}
```

`errors` aparece principalmente em validação (`422`).

---

## Códigos HTTP

| Código | Uso |
|--------|-----|
| `200` | Sucesso |
| `401` | Token ausente, inválido, expirado ou revogado |
| `403` | Token sem ability / IP não permitido / tenant inconsistente |
| `404` | Recurso não encontrado (fases futuras) |
| `422` | Validação |
| `429` | Rate limit |
| `500` | Erro interno |

---

## Rate limit

- Padrão: **60 req/min** por token (`API_RATE_LIMIT_PER_MINUTE`).
- Resposta `429` quando excedido.

---

## Idempotência (preparada)

Envie o header (opcional):

```http
Idempotency-Key: uuid-ou-chave-unica
```

Na Fase 1 a chave é apenas registrada nos logs. Persistência/replay virão depois.

---

## CORS

Configurado em `config/cors.php` para paths `api/*`.  
Defina `API_CORS_ALLOWED_ORIGINS` em produção (URLs separadas por vírgula).

---

## Multiempresa

- O tenant é resolvido **somente** pelo token (`ResolveApiTenant`).
- Não há regras por nome de empresa, CNPJ ou IDs fixos de clientes.
- Integrações (SAS-Estoque, mobile, marketplace, ERP, etc.) usam o **mesmo** mecanismo genérico.

---

## Exemplos

### cURL — status

```bash
curl -sS -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json" \
  "https://seu-dominio/api/v1/integration/status"
```

### Sem token (401)

```bash
curl -sS -H "Accept: application/json" \
  "https://seu-dominio/api/v1/integration/status"
```

### Criar token (CLI — infraestrutura)

```bash
php artisan vendaffacil:api-token \
  --empresa=1 \
  --nome="Integração homolog" \
  --env=homologacao \
  --abilities=api.visualizar
```

O comando imprime o token **uma vez**. Guarde em local seguro (não versionar).

---

## Painel da empresa

Menu: **Configurações → API**

- Status
- Tokens (listagem)
- Aplicações conectadas (rótulos genéricos)
- Logs
- Ambiente

CRUD completo de tokens na UI será evoluído; a geração via Artisan já está disponível.

---

## Segurança (Fase 1)

- Bearer Token com hash SHA-256
- Rate limit
- Respostas JSON padronizadas
- Exceções da API em JSON (`/api/*`)
- CORS preparado
- Logs sem gravar o token completo
- Validação centralizada (Laravel + envelope `ApiResponse`)
- Idempotência preparada (header capturado)

---

## Fora do escopo desta fase

Não existem endpoints de: produtos, clientes, pedidos, delivery, vendas, PDV, caixa, fiscal.
