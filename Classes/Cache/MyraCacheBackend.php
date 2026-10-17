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

namespace CPSIT\MyraCloudConnector\Cache;

use CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Event\ClearMyraCloudCacheEvent;
use CPSIT\MyraCloudConnector\Exception\InvalidCacheEntry;
use CPSIT\MyraCloudConnector\Exception\InvalidCacheFrontend;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal
 */
final class MyraCacheBackend extends Typo3DatabaseBackend
{
    private ?Connection $connection = null;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?AdapterProvider $adapterProvider = null;

    /**
     * @param list<string> $tags
     * @throws InvalidCacheEntry
     */
    public function set(string $entryIdentifier, string $data, array $tags = [], $lifetime = null): void
    {
        if (!MathUtility::canBeInterpretedAsInteger($data)) {
            throw new InvalidCacheEntry($data);
        }

        // Ignore lifetime since this is handled by MyraCloud
        parent::set($entryIdentifier, $data, $tags);
    }

    public function flush(): void
    {
        $this->getEventDispatcher()->dispatch(new ClearMyraCloudCacheEvent(Typo3CacheType::ALL_PAGE));

        parent::flush();
    }

    public function flushByTag($tag): void
    {
        $this->clearExternalCacheByTags([(string)$tag]);

        parent::flushByTag($tag);
    }

    public function flushByTags(array $tags): void
    {
        $this->clearExternalCacheByTags(array_values(array_map(strval(...), $tags)));

        parent::flushByTags($tags);
    }

    /**
     * One batch for all pages of all tags: one request per host, not per page.
     *
     * @param list<string> $tags
     */
    private function clearExternalCacheByTags(array $tags): void
    {
        $pageIds = [];
        foreach ($tags as $tag) {
            foreach ($this->getPageIdsByTag($tag) as $pageId) {
                $pageIds[$pageId] = $pageId;
            }
        }

        if ($pageIds === []) {
            return;
        }

        $this->getAdapterProvider()->batch(function () use ($pageIds): void {
            foreach ($pageIds as $pageId) {
                $this->getEventDispatcher()->dispatch(
                    new ClearMyraCloudCacheEvent(Typo3CacheType::PAGE, (string)$pageId),
                );
            }
        });
    }

    /**
     * Every page cached with the tag: a record tag such as "tt_content_5" is shared by
     * all pages showing the record, not only the first one.
     *
     * @return list<int>
     */
    private function getPageIdsByTag(string $tag): array
    {
        $cacheIdentifiers = $this->getConnection()->select(['identifier'], $this->tagsTable, ['tag' => $tag])->fetchFirstColumn();
        $pageIds = [];

        foreach ($cacheIdentifiers as $cacheIdentifier) {
            $pageId = is_string($cacheIdentifier) ? (int)$this->get($cacheIdentifier) : 0;

            if ($pageId > 0) {
                $pageIds[$pageId] = $pageId;
            }
        }

        return array_values($pageIds);
    }

    private function getConnection(): Connection
    {
        return $this->connection ??= GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
    }

    private function getAdapterProvider(): AdapterProvider
    {
        return $this->adapterProvider ??= GeneralUtility::makeInstance(AdapterProvider::class);
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher ??= GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    /**
     * @throws InvalidCacheFrontend
     */
    public function setCache(FrontendInterface $cache): void
    {
        // Only specific myra cache frontends are allowed
        if (!($cache instanceof MyraCacheFrontend)) {
            throw new InvalidCacheFrontend($cache);
        }

        parent::setCache($cache);
    }
}
