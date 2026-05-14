<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Módulo fiscal (preparação / drivers)
    |--------------------------------------------------------------------------
    */
    'certificados_disk' => env('FISCAL_CERTIFICADOS_DISK', 'local'),

    'certificados_diretorio' => env('FISCAL_CERTIFICADOS_DIR', 'fiscal-certificados'),

    'xml_disk' => env('FISCAL_XML_DISK', 'local'),

    'xml_diretorio' => env('FISCAL_XML_DIR', 'fiscal-xml'),

    'danfe_disk' => env('FISCAL_DANFE_DISK', 'local'),

    'danfe_diretorio' => env('FISCAL_DANFE_DIR', 'fiscal-danfe'),
];
