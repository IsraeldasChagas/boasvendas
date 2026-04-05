<?php

return [

    /*
    | E-mails (separados por vírgula) que acessam o painel master /admin.
    | Ex.: BOASVENDAS_ADMIN_EMAILS=admin@exemplo.com,outro@exemplo.com
    */

    'admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('BOASVENDAS_ADMIN_EMAILS', ''))
    ))),

    /*
    | Taxa de entrega padrão (R$) na loja pública, quando a empresa não tiver valor próprio.
    */
    'taxa_entrega_padrao' => (float) env('BOASVENDAS_TAXA_ENTREGA', 5.99),

];
