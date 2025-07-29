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

namespace CPSIT\CpsMyraCloud\DataHandler;

use CPSIT\CpsMyraCloud\AdapterProvider\AdapterProvider;
use CPSIT\CpsMyraCloud\Domain\Enum\Typo3CacheType;
use CPSIT\CpsMyraCloud\Service\ExternalCacheService;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;

class DataHandlerHook implements SingletonInterface
{
    private array $pageAlreadyCleared = [];

    public function __construct(
        private readonly ExternalCacheService $externalCacheService,
        private readonly AdapterProvider $provider
    ) {}

    /**
     * @param array $data
     * @param DataHandler $dataHandler
     */
    public function clearCachePostProc(array $data, DataHandler $dataHandler): void
    {
        if (isset($data['uid']) && isset($data['table']) && isset($data['uid_page'])) {
            $pid = (int)$data['uid_page'];
            $provider = $this->provider->getDefaultProviderItem();
            if ($provider && !($this->pageAlreadyCleared[$pid] ?? false) && $provider->canAutomated()) {
                try {
                    $this->pageAlreadyCleared[$pid] = $this->externalCacheService->clear(Typo3CacheType::PAGE, (string)$pid);
                } catch (\Exception) {
                }
            }
        }
    }
}
