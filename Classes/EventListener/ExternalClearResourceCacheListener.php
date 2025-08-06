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

namespace CPSIT\MyraCloudConnector\EventListener;

use CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\FileAdmin;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\FileInterface as MyraFileInterface;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Domain\Repository\FileRepository;
use CPSIT\MyraCloudConnector\Service\ExternalCacheService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Event\AfterFileCommandProcessedEvent;
use TYPO3\CMS\Core\Resource\FileInterface;

final readonly class ExternalClearResourceCacheListener
{
    public function __construct(
        private ExternalCacheService $externalCacheService,
        private FileRepository $fileRepository,
        private AdapterProvider $provider,
        #[Autowire('@cache.runtime')]
        private FrontendInterface $cache,
    ) {}

    public function __invoke(AfterFileCommandProcessedEvent $event): void
    {
        $action = array_key_first($event->getCommand());

        if ($action === 'upload' && $event->getConflictMode() === DuplicationBehavior::REPLACE) {
            $provider = $this->provider->getDefaultProviderItem();

            if ($provider && $provider->canAutomated()) {
                $this->clearCacheForFiles($event->getResult());
            }
        }
    }

    private function clearCacheForFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof FileInterface) {
                $this->clearCacheForFile($file);
            }
        }
    }

    private function clearCacheForFile(FileInterface $file): void
    {
        $path = $file->getIdentifier();
        $files = $this->getProcessedFiles($file);
        $files[] = new FileAdmin($path);

        foreach ($files as $toClearFile) {
            $this->clearMyraFile($toClearFile);
        }
    }

    private function getProcessedFiles(FileInterface $file): array
    {
        try {
            $files = $this->fileRepository->getProcessedFilesFromFile($file);
        } catch (\Exception) {
            $files = [];
        }

        return $files;
    }

    private function clearMyraFile(MyraFileInterface $file): void
    {
        // TODO: add other storages here not only (1:)
        $path = '1:/' . ltrim($file->getRawSlug(), '/');
        $cacheIdentifier = 'myra-cloud-file-list-' . crc32($path);

        if ($this->cache->get($cacheIdentifier) === false) {
            try {
                $this->cache->set(
                    $cacheIdentifier,
                    $this->externalCacheService->clear(Typo3CacheType::RESOURCE, $path),
                );
            } catch (\Exception) {
            }
        }
    }
}
