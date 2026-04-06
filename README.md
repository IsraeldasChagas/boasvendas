# Venda Fácil (vendaffacil.com.br)

SaaS multiempresa: delivery, cardápio digital, pedidos, financeiro, venda externa (consignação / fiado) e painel master — em **Laravel**, **Blade** e **Bootstrap 5**.

## Requisitos

- PHP **8.2+** e [Composer](https://getcomposer.org/)
- Projeto **Laravel 12.x**, compatível com PHP 8.2.

## Instalação

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Ajuste no `.env`: `APP_URL` (ex.: `https://vendaffacil.com.br`), base de dados e `VENDAFFACIL_ADMIN_EMAILS` para o painel `/admin`.

## Executar localmente

```bash
php artisan serve
```

Acesse [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Estrutura principal

| Área | Controllers | Views |
|------|-------------|-------|
| Site | `app/Http/Controllers/Site` | `resources/views/site` |
| Auth | `app/Http/Controllers/Auth` | `resources/views/auth` |
| Cliente (público) | `app/Http/Controllers/Publico` | `resources/views/publico` |
| Empresa | `app/Http/Controllers/Empresa` | `resources/views/empresa` |
| Master | `app/Http/Controllers/Admin` | `resources/views/admin` |

Layouts: `resources/views/layouts/{site,auth,publico,empresa,admin}.blade.php`.

Assets: `public/assets/css/vendaffacil.css`, `public/assets/js/vendaffacil.js`, `public/assets/img/`.

## PWA (base)

- Manifest: `public/pwa/manifest.json`

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

**Venda Fácil** — vendaffacil.com.br
