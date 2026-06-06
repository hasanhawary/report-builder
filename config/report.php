<?php

return [
    'routes' => [
        'enabled' => true,
        'prefix' => 'api/report',
        'middleware' => ['api'],
        'name_prefix' => 'report.',
        'paths' => [
            'report' => '/',
        ],
        'names' => [
            'report' => 'index',
        ],
    ],

    // Optional: set a global namespace for host application report classes.
    // You may also set a page-specific class via report.pages.{page}.class.
    'namespace' => null,

    'defaults' => [
        'page' => null,
        'prefer_chart' => 'high_chart',
    ],

    'component_errors' => 'throw',

    'database' => [
        'disable_mysql_strict_mode' => true,
    ],

    // Translation settings for role display names
    'translate' => [
        'enabled' => true,
        'trans_file' => 'report',
        'file' => 'report'
    ],

    'pages' => [],
];
