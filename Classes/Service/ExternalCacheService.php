<?php

declare(strict_types=1);

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

namespace CPSIT\CpsMyraCloud\Service;

use CPSIT\CpsMyraCloud\Adapter\AdapterInterface;
use CPSIT\CpsMyraCloud\AdapterProvider\AdapterProvider;
use CPSIT\CpsMyraCloud\Domain\DTO\Provider\ProviderItemRegisterInterface;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\CustomFile;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\FileAdmin;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\FileInterface;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\Typo3Conf;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\Typo3Core;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\Typo3Temp;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\PageSlugInterface;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\SiteConfigInterface;
use CPSIT\CpsMyraCloud\Domain\Enum\Typo3CacheType;

readonly class ExternalCacheService
{
    public function __construct(
        private PageService $pageService,
        private SiteService $siteService,
        private AdapterProvider $provider
    ) {}

    /**
     * @param Typo3CacheType $type
     * @param string $identifier
     * @return bool
     */
    public function clear(Typo3CacheType $type, string $identifier): bool
    {
        $providerItem = $this->provider->getDefaultProviderItem();
        if ($providerItem === null) {
            return false;
        }

        if ($type === Typo3CacheType::PAGE) {
            return $this->clearPage($providerItem, (int)$identifier);
        }
        if ($type === Typo3CacheType::RESOURCE) {
            return $this->clearFile($providerItem, trim($identifier));
        }
        if ($type === Typo3CacheType::ALL_PAGE) {
            return $this->clearAllPages($providerItem);
        }
        if ($type === Typo3CacheType::ALL_RESOURCES) {
            return $this->clearAllFiles($providerItem);
        }

        return false;
    }

    /**
     * @param ProviderItemRegisterInterface $provider
     * @param int $pageUid
     * @return bool
     */
    private function clearPage(ProviderItemRegisterInterface $provider, int $pageUid): bool
    {
        $page = $this->pageService->getPage($pageUid);
        $sites = $this->siteService->getSitesForClearance($page);
        return $this->clearCacheWithAdapter($provider->getAdapter(), $sites, $page);
    }

    /**
     * @param ProviderItemRegisterInterface $provider
     * @return bool
     */
    private function clearAllPages(ProviderItemRegisterInterface $provider): bool
    {
        $sites = $this->siteService->getSitesForClearance(null);
        return $this->clearCacheWithAdapter($provider->getAdapter(), $sites, null, true);
    }

    /**
     * @param ProviderItemRegisterInterface $provider
     * @return bool
     */
    private function clearAllFiles(ProviderItemRegisterInterface $provider): bool
    {
        $sites = $this->siteService->getSitesForClearance(null);
        $fileCaches = [new FileAdmin(), new Typo3Temp(), new Typo3Conf(), new Typo3Core()];
        $result = 0;
        foreach ($fileCaches as $file) {
            $result |= $this->clearCacheWithAdapter($provider->getAdapter(), $sites, $file, true);
        }

        return (bool)$result;
    }

    /**
     * @param ProviderItemRegisterInterface $provider
     * @param string $relPath
     * @return bool
     */
    private function clearFile(ProviderItemRegisterInterface $provider, string $relPath): bool
    {
        $file = $this->getFile($relPath);
        $sites = $this->siteService->getSitesForClearance(null);
        // files are always recursive deleted
        return $this->clearCacheWithAdapter($provider->getAdapter(), $sites, $file, true);
    }

    /**
     * @param string $identifier
     * @return FileInterface
     */
    private function getFile(string $identifier): FileInterface
    {
        if (str_starts_with($identifier, '1:/')) {
            return new FileAdmin(substr($identifier, 3));
        }
        // TODO: add other storages here
        return new CustomFile($identifier);
    }

    /**
     * @param AdapterInterface $adapter
     * @param SiteConfigInterface[] $sites
     * @param PageSlugInterface|null $slug
     * @param bool $recursive
     * @return bool
     */
    private function clearCacheWithAdapter(AdapterInterface $adapter, array $sites, ?PageSlugInterface $slug = null, bool $recursive = false): bool
    {
        $result = 0;
        foreach ($sites as $site) {
            $result |= $adapter->clearCache($site, $slug, $recursive);
        }

        return (bool)$result;
    }
}
