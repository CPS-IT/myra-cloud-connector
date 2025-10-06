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

use CPSIT\MyraCloudConnector\Adapter\AdapterInterface;
use CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider;
use CPSIT\MyraCloudConnector\Domain\DTO\Provider\ProviderItemRegisterInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\CustomFile;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\ExtensionAsset;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\File;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\FileInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\Typo3Core;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\Typo3Temp;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\PageSlugInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\SiteConfigInterface;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;

final readonly class ExternalCacheService
{
    public function __construct(
        private PageService $pageService,
        private SiteService $siteService,
        private AdapterProvider $provider,
        private ResourceFactory $resourceFactory,
        private StorageRepository $storageRepository,
    ) {}

    public function clear(Typo3CacheType $type, string $identifier, ?int $languageId = null): bool
    {
        $providerItem = $this->provider->getDefaultProviderItem();
        if ($providerItem === null) {
            return false;
        }

        if ($type === Typo3CacheType::PAGE) {
            return $this->clearPage($providerItem, (int)$identifier, $languageId);
        }
        if ($type === Typo3CacheType::RESOURCE) {
            return $this->clearFile($providerItem, trim($identifier));
        }
        if ($type === Typo3CacheType::ALL_PAGE) {
            return $this->clearAllPages($providerItem, $languageId);
        }
        if ($type === Typo3CacheType::ALL_RESOURCES) {
            return $this->clearAllFiles($providerItem);
        }

        return false;
    }

    private function clearPage(ProviderItemRegisterInterface $provider, int $pageUid, ?int $languageId = null): bool
    {
        $page = $this->pageService->getPage($pageUid, $languageId);
        $sites = $this->siteService->getSitesForClearance($page);
        return $this->clearCacheWithAdapter($provider->getAdapter(), $sites, $page);
    }

    private function clearAllPages(ProviderItemRegisterInterface $provider, ?int $languageId = null): bool
    {
        $sites = $this->siteService->getSitesForClearance(null);
        return $this->clearCacheWithAdapter($provider->getAdapter(), $sites, null, true);
    }

    private function clearAllFiles(ProviderItemRegisterInterface $provider): bool
    {
        $sites = $this->siteService->getSitesForClearance(null);
        $fileCaches = $this->getAllFileStorages();
        $fileCaches[] = new ExtensionAsset();
        $fileCaches[] = new Typo3Core();
        $fileCaches[] = new Typo3Temp();
        $result = 0;

        foreach ($fileCaches as $file) {
            $result |= $this->clearCacheWithAdapter($provider->getAdapter(), $sites, $file, true);
        }

        return (bool)$result;
    }

    private function clearFile(ProviderItemRegisterInterface $provider, string $relPath): bool
    {
        $file = $this->getFile($relPath);
        $sites = $this->siteService->getSitesForClearance(null);
        // files are always deleted recursively
        return $this->clearCacheWithAdapter($provider->getAdapter(), $sites, $file, true);
    }

    /**
     * @return list<File>
     */
    private function getAllFileStorages(): array
    {
        $storageObjects = $this->storageRepository->findAll();
        $storages = [];

        foreach ($storageObjects as $storage) {
            $rootFolder = $storage->getRootLevelFolder();
            $storages[] = new File($rootFolder);
        }

        return $storages;
    }

    private function getFile(string $identifier): FileInterface
    {
        try {
            $file = $this->resourceFactory->retrieveFileOrFolderObject($identifier);
        } catch (ResourceDoesNotExistException) {
            $file = null;
        }

        if ($file !== null) {
            return new File($file);
        }

        return new CustomFile($identifier);
    }

    /**
     * @param SiteConfigInterface[] $sites
     */
    private function clearCacheWithAdapter(
        AdapterInterface $adapter,
        array $sites,
        ?PageSlugInterface $slug = null,
        bool $recursive = false,
    ): bool {
        $result = 0;
        foreach ($sites as $site) {
            $result |= $adapter->clearCache($site, $slug, $recursive);
        }

        return (bool)$result;
    }
}
