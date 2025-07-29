<?php

/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'MyraCloud Connector',
    'description' => 'Clear-Cache for MyraCloud systems',
    'category' => 'be',
    'author' => 'coding. powerful. systems. CPS GmbH',
    'author_email' => 'b.rannow@familie-redlich.de',
    'author_company' => 'coding. powerful. systems. CPS GmbH',
    'state' => 'alpha',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
