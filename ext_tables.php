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

use CPSIT\MyraCloudConnector\ButtonBar\ExternalClearCacheButtonBarItemProvider;
use CPSIT\MyraCloudConnector\CacheActionMenu\ExternalClearCacheMenuItemProvider;
use CPSIT\MyraCloudConnector\ContextMenu\ExternalClearCacheContextMenuItemProvider;
use CPSIT\MyraCloudConnector\DataHandler\DataHandlerHook;
use CPSIT\MyraCloudConnector\Extension;
use CPSIT\MyraCloudConnector\FileList\FileListHook;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][Extension::KEY . '_clearCache'] = ExternalClearCacheButtonBarItemProvider::class . '->clearPageCache';
$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][Extension::KEY . '_clearCache'] = ExternalClearCacheContextMenuItemProvider::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][Extension::KEY . '_clearCache'] = ExternalClearCacheMenuItemProvider::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][Extension::KEY] = FileListHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][Extension::KEY] = DataHandlerHook::class . '->clearCachePostProc';
