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

defined('TYPO3') or die();

(function ($extKey) {

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][$extKey . '_clearCache'] = CPSIT\CpsMyraCloud\ButtonBar\ExternalClearCacheButtonBarItemProvider::class . '->clearPageCache';
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][$extKey . '_clearCache'] = CPSIT\CpsMyraCloud\ContextMenu\ExternalClearCacheContextMenuItemProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions'][$extKey . '_clearCache'] = CPSIT\CpsMyraCloud\CacheActionMenu\ExternalClearCacheMenuItemProvider::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][$extKey] = CPSIT\CpsMyraCloud\FileList\FileListHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][$extKey] = CPSIT\CpsMyraCloud\DataHandler\DataHandlerHook::class . '->clearCachePostProc';

})('cps_myra_cloud');
