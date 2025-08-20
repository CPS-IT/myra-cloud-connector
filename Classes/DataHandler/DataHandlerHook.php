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

namespace CPSIT\MyraCloudConnector\DataHandler;

use CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Service\ExternalCacheService;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\SingletonInterface;

#[Autoconfigure(public: true)]
class DataHandlerHook implements SingletonInterface
{
    /**
     * @var array<int, bool>
     */
    private array $pageAlreadyCleared = [];

    public function __construct(
        private readonly ExternalCacheService $externalCacheService,
        private readonly AdapterProvider $provider
    ) {}

    /**
     * @param array{table: string, uid: int, uid_page: int}|array{cacheCmd: string, tags: list<string>} $data
     */
    public function clearCachePostProc(array $data): void
    {
        if (isset($data['uid'], $data['table'], $data['uid_page'])) {
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
