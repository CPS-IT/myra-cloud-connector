<?php

declare(strict_types=1);

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

namespace CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Typo3Core extends AbstractFile
{
    private static ?string $resolvedPrefix = null;

    protected function getPrefix(): string
    {
        if (self::$resolvedPrefix === null) {
            $backendEntryPointResolver = GeneralUtility::makeInstance(BackendEntryPointResolver::class);
            $serverRequest = $this->getServerRequest();
            self::$resolvedPrefix = rtrim($backendEntryPointResolver->getPathFromRequest($serverRequest), '/');
        }

        return self::$resolvedPrefix;
    }

    private function getServerRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
    }
}
