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

namespace CPSIT\CpsMyraCloud\FileList;

use CPSIT\CpsMyraCloud\AdapterProvider\AdapterProvider;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\FileAdmin;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File\FileInterface as MyraFileInterface;
use CPSIT\CpsMyraCloud\Domain\Enum\Typo3CacheType;
use CPSIT\CpsMyraCloud\Domain\Repository\FileRepository;
use CPSIT\CpsMyraCloud\Service\ExternalCacheService;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;

class FileListHook implements SingletonInterface
{
    private array $pageAlreadyCleared = [];

    public function __construct(
        private readonly ExternalCacheService $externalCacheService,
        private readonly FileRepository $fileRepository,
        private readonly AdapterProvider $provider
    ) {}

    /**
     * @param string $action
     * @param array $cmdArr
     * @param array $result
     * @param ExtendedFileUtility $parentObject
     */
    public function processData_postProcessAction($action, array $cmdArr, array $result, ExtendedFileUtility $parentObject): void
    {
        if ($action === 'upload' && $parentObject->getExistingFilesConflictMode() === DuplicationBehavior::REPLACE) {
            $provider = $this->provider->getDefaultProviderItem();
            if ($provider && $provider->canAutomated()) {
                $this->clearCacheForFileGroups($result);
            }
        }
    }

    /**
     * @param array $groups
     */
    private function clearCacheForFileGroups(array $groups): void
    {
        foreach ($groups as $group) {
            $this->clearCacheForFiles($group);
        }
    }

    /**
     * @param array $files
     */
    private function clearCacheForFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof FileInterface) {
                $this->clearCacheForFile($file);
            }
        }
    }

    /**
     * @param FileInterface $file
     */
    private function clearCacheForFile(FileInterface $file): void
    {
        $path = $file->getIdentifier();
        $files = $this->getProcessedFiles($file);
        $files[] = new FileAdmin($path);

        foreach ($files as $toClearFile) {
            $this->clearMyraFile($toClearFile);
        }
    }

    /**
     * @param FileInterface $file
     * @return array
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

    /**
     * @param MyraFileInterface $file
     */
    private function clearMyraFile(MyraFileInterface $file): void
    {
        // TODO: add other storages here not only (1:)
        $path = '1:/' . ltrim($file->getRawSlug(), '/');
        $crc = crc32($path);
        if (!($this->pageAlreadyCleared[$crc] ?? false)) {
            try {
                $this->pageAlreadyCleared[$crc] = $this->externalCacheService->clear(Typo3CacheType::RESOURCE, $path);
            } catch (\Exception) {
                $this->pageAlreadyCleared[$crc] = false;
            }
        }
    }
}
