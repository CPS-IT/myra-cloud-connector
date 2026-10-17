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
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
    {
        if (!PageCacheEntry::isValid($data)) {
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
        $entries = [];
        foreach ($tags as $tag) {
            foreach ($this->getPageCacheEntriesByTag($tag) as $entry) {
                $entries[$entry->toString()] = $entry;
            }
        }

        if ($entries === []) {
            return;
        }

        $this->getAdapterProvider()->batch(function () use ($entries): void {
            foreach ($entries as $entry) {
                // Without a language (entries up to 3.3 / 4.1), the page's default language
                // is cleared.
                $this->getEventDispatcher()->dispatch(
                    new ClearMyraCloudCacheEvent(Typo3CacheType::PAGE, (string)$entry->pageId, $entry->languageId),
                );
            }
        });
    }

    /**
     * Every page cached with the tag, in every language it was cached in: a record tag such
     * as "tt_content_5" is shared by all pages showing the record, not only the first one.
     *
     * @return list<PageCacheEntry>
     */
    private function getPageCacheEntriesByTag(string $tag): array
    {
        $cacheIdentifiers = $this->getConnection()->select(['identifier'], $this->tagsTable, ['tag' => $tag])->fetchFirstColumn();
        $entries = [];

        foreach ($cacheIdentifiers as $cacheIdentifier) {
            $value = is_string($cacheIdentifier) ? $this->get($cacheIdentifier) : false;
            $entry = is_string($value) ? PageCacheEntry::fromString($value) : null;

            if ($entry !== null) {
                $entries[$entry->toString()] = $entry;
            }
        }

        return array_values($entries);
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
