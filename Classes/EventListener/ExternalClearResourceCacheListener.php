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
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\File as MyraFile;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Domain\Repository\FileRepository;
use CPSIT\MyraCloudConnector\Service\ExternalCacheService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Event\AfterFileCommandProcessedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;

#[AsEventListener('cpsit/myra-cloud-connector/external-clear-resource-cache')]
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

        if ($action === 'upload' && $event->getConflictMode() === DuplicationBehavior::REPLACE->value) {
            $provider = $this->provider->getDefaultProviderItem();
            /** @var File[]|bool $result */
            $result = $event->getResult();

            if (\is_array($result) && $provider?->canAutomated()) {
                $this->clearCacheForFiles($result);
            }
        }
    }

    /**
     * @param File[] $files
     */
    private function clearCacheForFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->clearCacheForFile($file);
        }
    }

    private function clearCacheForFile(FileInterface $file): void
    {
        $files = $this->getProcessedFiles($file);
        $files[] = new MyraFile($file);

        foreach ($files as $toClearFile) {
            $this->clearMyraFile($toClearFile);
        }
    }

    /**
     * @return list<MyraFile>
     */
    private function getProcessedFiles(FileInterface $file): array
    {
        try {
            $files = $this->fileRepository->getProcessedFilesFromFile($file);
        } catch (\Exception) {
            $files = [];
        }

        return $files;
    }

    private function clearMyraFile(MyraFile $file): void
    {
        $path = $file->getCombinedIdentifier();
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
