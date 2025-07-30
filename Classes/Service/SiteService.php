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

namespace CPSIT\MyraCloudConnector\Service;

use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\PageIdInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\SiteConfigExternalIdentifierInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\SiteConfigInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\Typo3SiteConfig;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

class SiteService implements SingletonInterface
{
    public function __construct(
        private readonly SiteFinder $siteFinder
    ) {}

    /**
     * @param PageIdInterface|null $pageId
     * @return SiteConfigInterface[]
     */
    public function getSitesForClearance(?PageIdInterface $pageId): array
    {
        if ($pageId) {
            return $this->getAllSupportedSitesForPageId($pageId);
        }

        return $this->getAllSupportedSites();
    }

    /**
     * @return SiteConfigInterface[]
     */
    private function getAllSupportedSites(): array
    {
        // TODO: caching ?
        $sites = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            $siteConfig = new Typo3SiteConfig($site);
            if ($this->isSiteSupported($siteConfig)) {
                $sites[] = $siteConfig;
            }
        }

        return $sites;
    }

    /**
     * @param PageIdInterface $pageId
     * @return SiteConfigInterface[]
     */
    private function getAllSupportedSitesForPageId(PageIdInterface $pageId): array
    {
        // todo: caching?
        try {
            $site = $this->siteFinder->getSiteByPageId($pageId->getPageId());
            $siteConfig = new Typo3SiteConfig($site);
        } catch (\Exception) {
            return [];
        }

        if ($this->isSiteSupported($siteConfig)) {
            return [$siteConfig];
        }

        return [];
    }

    /**
     * @param SiteConfigExternalIdentifierInterface $site
     * @return bool
     */
    private function isSiteSupported(SiteConfigExternalIdentifierInterface $site): bool
    {
        return !empty($site->getExternalIdentifierList());
    }
}
