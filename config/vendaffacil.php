<?php

/*
| E-mails master: se VENDAFFACIL_ADMIN_EMAILS existir no .env mas estiver vazio,
| env() devolve '' e o default do segundo parâmetro NÃO é aplicado — por isso tratamos aqui.
*/
$adminRaw = env('VENDAFFACIL_ADMIN_EMAILS', env('BOASVENDAS_ADMIN_EMAILS'));
if ($adminRaw === null || trim((string) $adminRaw) === '') {
    $adminRaw = 'admin@vendaffacil.com.br';
}

return [

    /*
    | Nome exibido no site (títulos, navbar, textos com config('app.name')).
    | Não depende de APP_NAME no .env — útil após renomear o projeto sem mexer em variáveis legadas.
    | Para personalizar: VENDAFFACIL_SITE_NAME="Sua marca"
    */
    'site_name' => env('VENDAFFACIL_SITE_NAME', 'Vendaffacil'),

    /*
    | E-mails (separados por vírgula) que acessam o painel master /admin.
    | Em produção defina VENDAFFACIL_ADMIN_EMAILS com o(s) e-mail(s) real(is).
    */

    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) $adminRaw)
    ))),

    /*
    | Taxa de entrega padrão (R$) na loja pública, quando a empresa não tiver valor próprio.
    */
    'taxa_entrega_padrao' => (float) env('VENDAFFACIL_TAXA_ENTREGA', env('BOASVENDAS_TAXA_ENTREGA', 5.99)),

];
