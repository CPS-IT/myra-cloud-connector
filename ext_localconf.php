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
use CPSIT\MyraCloudConnector\DataHandler\DataHandlerHook;
use CPSIT\MyraCloudConnector\Extension;

defined('TYPO3') or die();

// Configure myracloud cache
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['myracloud'] = [
    'backend' => MyraCacheBackend::class,
    'frontend' => MyraCacheFrontend::class,
    'groups' => ['pages'],
];

// Hooks belong here: ext_tables.php is not loaded for frontend requests without a backend
// user, so records saved by DataHandler there did not clear Myra.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][Extension::KEY] = DataHandlerHook::class . '->clearCachePostProc';
