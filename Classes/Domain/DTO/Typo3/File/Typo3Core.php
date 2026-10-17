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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Typo3Core extends AbstractFile
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
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        return $request instanceof ServerRequestInterface ? $request : $this->createCliRequest();
    }

    /**
     * CLI has no request, and ServerRequestFactory::fromGlobals() throws there. The URL path
     * TYPO3 is installed under cannot be known without a request (a site base such as
     * "https://example.com/de/" is a site prefix, not the installation path), so the
     * installation is taken to be the web root, the Composer default: the backend is
     * "/typo3", or the path of an entry point configured as URL.
     */
    private function createCliRequest(): ServerRequestInterface
    {
        $publicPath = Environment::getPublicPath();
        $serverParams = ['HTTP_HOST' => 'localhost', 'SCRIPT_NAME' => '/index.php'];

        return (new ServerRequest('http://localhost/index.php', 'GET', null, [], $serverParams))
            ->withAttribute('normalizedParams', new NormalizedParams($serverParams, [], $publicPath . '/index.php', $publicPath . '/'));
    }
}
