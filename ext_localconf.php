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

use CPSIT\MyraCloudConnector\Cache\MyraCacheBackend;
use CPSIT\MyraCloudConnector\Cache\MyraCacheFrontend;

defined('TYPO3') or die();

// Configure myracloud cache
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['myracloud'] = [
    'backend' => MyraCacheBackend::class,
    'frontend' => MyraCacheFrontend::class,
    'groups' => ['pages'],
];
