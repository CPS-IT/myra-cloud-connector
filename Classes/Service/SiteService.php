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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

final readonly class SiteService
{
    public function __construct(
        private SiteFinder $siteFinder,
        #[Autowire('@cache.runtime')]
        private FrontendInterface $cache,
    ) {}

    /**
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
        $cacheIdentifier = 'myra-cloud-supported-sites';

        if (($sites = $this->cache->get($cacheIdentifier)) === false) {
            $sites = [];

            foreach ($this->siteFinder->getAllSites() as $site) {
                $siteConfig = new Typo3SiteConfig($site);

                if ($this->isSiteSupported($siteConfig)) {
                    $sites[] = $siteConfig;
                }
            }

            $this->cache->set($cacheIdentifier, $sites);
        }

        return $sites;
    }

    /**
     * @return SiteConfigInterface[]
     */
    private function getAllSupportedSitesForPageId(PageIdInterface $pageId): array
    {
        $cacheIdentifier = 'myra-cloud-supported-sites-page-' . crc32(serialize($pageId));

        if (($sites = $this->cache->get($cacheIdentifier)) === false) {
            try {
                $site = $this->siteFinder->getSiteByPageId($pageId->getTranslationSource() ?? $pageId->getPageId());
                $siteConfig = new Typo3SiteConfig($site);

                if ($this->isSiteSupported($siteConfig)) {
                    $sites = [$siteConfig];
                } else {
                    $sites = [];
                }
            } catch (\Exception) {
                $sites = [];
            }

            $this->cache->set($cacheIdentifier, $sites);
        }

        return $sites;
    }

    private function isSiteSupported(SiteConfigExternalIdentifierInterface $site): bool
    {
        return $site->getExternalIdentifierList() !== [];
    }
}
