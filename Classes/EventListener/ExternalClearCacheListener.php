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

use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Event\ClearMyraCloudCacheEvent;
use CPSIT\MyraCloudConnector\Service\ExternalCacheService;
use CPSIT\MyraCloudConnector\Service\PageService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

#[AsEventListener('cpsit/myra-cloud-connector/external-clear-cache')]
final readonly class ExternalClearCacheListener
{
    public function __construct(
        private ExternalCacheService $cacheService,
        private PageService $pageService,
        #[Autowire('@cache.runtime')]
        private FrontendInterface $runtimeCache,
    ) {}

    public function __invoke(ClearMyraCloudCacheEvent $event): void
    {
        if ($event->type === Typo3CacheType::PAGE) {
            $result = $this->clearPageCache($event);
        } else {
            $result = $this->clearExternalCache($event->type, $event->identifier, $event->languageId);
        }

        $event->setCacheResult($result);
    }

    private function clearPageCache(ClearMyraCloudCacheEvent $event): bool
    {
        $pageId = $event->identifier;
        $languageId = $event->languageId;

        if ($event->languageId === null) {
            [$pageId, $languageId] = $this->resolveParametersForPage((int)$pageId);
        }

        return $this->clearExternalCache(Typo3CacheType::PAGE, $pageId, $languageId);
    }

    private function clearExternalCache(Typo3CacheType $type, ?string $identifier, ?int $languageId): bool
    {
        $cacheIdentifier = 'MyraCloudConnector_ExternalClearCache_' . $identifier . '_' . $languageId;
        $result = $this->runtimeCache->get($cacheIdentifier);

        if (!$result) {
            $result = $this->cacheService->clear($type, $identifier, $languageId);

            $this->runtimeCache->set($cacheIdentifier, $result);
        }

        return $result;
    }

    /**
     * @return array{string|null, int}
     */
    private function resolveParametersForPage(int $recordUid): array
    {
        $page = $this->pageService->getPage($recordUid);
        $pageUid = $page?->getOriginalPageId();
        $languageId = $page?->getLanguageId() ?? 0;

        if ($pageUid !== null) {
            $pageUid = (string)$pageUid;
        }

        return [$pageUid, $languageId];
    }
}
