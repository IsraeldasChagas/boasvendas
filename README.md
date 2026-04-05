# Boas Vendas

SaaS multiempresa (demonstração **front-end**): delivery, cardápio digital, pedidos, financeiro, venda externa (consignação / fiado) e painel master — em **Laravel**, **Blade** e **Bootstrap 5**.

## Requisitos

- PHP **8.2+** e [Composer](https://getcomposer.org/)
- Para a versão mais recente do Laravel (13.x) é necessário PHP **8.3+**. Neste ambiente o projeto foi gerado como **Laravel 12.x**, compatível com PHP 8.2.

## Instalação

O projeto já pode existir em `C:\boasvandas`. Em outra máquina:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

## Executar localmente

```bash
php artisan serve
```

Acesse [http://127.0.0.1:8000](http://127.0.0.1:8000).

## O que este repositório faz (nesta fase)

- **Sem** uso de banco de dados nas telas customizadas: conteúdo **mockado** nas views.
- **Sem** autenticação real, **sem** APIs de negócio: controllers apenas retornam views.
- **Com** navegação completa entre site institucional, auth (mock), loja do cliente, painel da empresa (incluindo venda externa) e painel admin master.

O esqueleto Laravel pode ter criado `database/database.sqlite` e migrations padrão na instalação; as rotas do **Boas Vendas** **não dependem** disso para funcionar.

## Estrutura principal

| Área | Controllers | Views |
|------|-------------|--------|
| Site | `app/Http/Controllers/Site` | `resources/views/site` |
| Auth | `app/Http/Controllers/Auth` | `resources/views/auth` |
| Cliente (público) | `app/Http/Controllers/Publico` | `resources/views/publico` |
| Empresa | `app/Http/Controllers/Empresa` | `resources/views/empresa` |
| Master | `app/Http/Controllers/Admin` | `resources/views/admin` |

Layouts: `resources/views/layouts/{site,auth,publico,empresa,admin}.blade.php`.

Assets: `public/assets/css`, `public/assets/js`, `public/assets/img`.

Componentes reutilizáveis (partials): `resources/views/partials/`.

## PWA (base)

- Manifest: `public/pwa/manifest.json`
- Ícones gerados em `public/pwa/icons/` (substitua pelos definitivos da marca).
- Opcional: registrar um `service worker` quando for habilitar modo offline.

## Deploy

- Configure `APP_URL` e `APP_ENV=production` no `.env`.
- Execute `php artisan config:cache` e `php artisan route:cache` em produção.
- Aponte o document root do servidor web para a pasta `public/`.

## Atalhos de navegação (demo)

- Site: `/`, `/planos`, `/sobre`, `/contato`
- Loja exemplo: `/loja/demo`
- Painel empresa: `/empresa/dashboard`
- Painel master: `/admin/dashboard`

---

**Boas Vendas** — estrutura pronta para evoluir com banco, autenticação e APIs quando você precisar.
