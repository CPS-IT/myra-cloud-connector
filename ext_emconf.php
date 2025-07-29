<?php

/*
 * This file is part of the TYPO3 CMS extension "cps_myra_cloud".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
