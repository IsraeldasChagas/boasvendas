<?php

return [

    /*
    | E-mails (separados por vírgula) que acessam o painel master /admin.
    | Ex.: VENDAFFACIL_ADMIN_EMAILS=admin@exemplo.com,outro@exemplo.com
    */

    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('VENDAFFACIL_ADMIN_EMAILS', env('BOASVENDAS_ADMIN_EMAILS', '')))
    ))),

    /*
    | Taxa de entrega padrão (R$) na loja pública, quando a empresa não tiver valor próprio.
    */
    'taxa_entrega_padrao' => (float) env('VENDAFFACIL_TAXA_ENTREGA', env('BOASVENDAS_TAXA_ENTREGA', 5.99)),

];
