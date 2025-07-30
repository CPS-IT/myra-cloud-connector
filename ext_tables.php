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

defined('TYPO3') or die();

(function ($extKey) {

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][$extKey . '_clearCache'] = CPSIT\MyraCloudConnector\ButtonBar\ExternalClearCacheButtonBarItemProvider::class . '->clearPageCache';
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][$extKey . '_clearCache'] = CPSIT\MyraCloudConnector\ContextMenu\ExternalClearCacheContextMenuItemProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][$extKey . '_clearCache'] = CPSIT\MyraCloudConnector\CacheActionMenu\ExternalClearCacheMenuItemProvider::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][$extKey] = CPSIT\MyraCloudConnector\FileList\FileListHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$extKey] = CPSIT\MyraCloudConnector\DataHandler\DataHandlerHook::class . '->clearCachePostProc';

})('myra_cloud_connector');
