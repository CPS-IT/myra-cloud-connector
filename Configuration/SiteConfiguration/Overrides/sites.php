<?php

/*
 * This file is part of the TYPO3 CMS extension "myra_cloud_connector".
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

$GLOBALS['SiteConfiguration']['site']['columns']['myra_host'] = [
    'label' => 'LLL:EXT:myra_cloud_connector/Resources/Private/Language/locallang_myra.xlf:tca.site.cache.identifier',
    'config' => [
        'type' => 'input',
        'default' => '',
        'placeholder' => '',
        'size' => 255,
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',--div--;Myra Cloud,myra_host';
