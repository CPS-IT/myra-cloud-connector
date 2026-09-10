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

    /**
     * @param list<string> $tags
     * @throws InvalidCacheEntry
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
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
        $this->clearExternalCacheByTag($tag);

        parent::flushByTag($tag);
    }

    public function flushByTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->clearExternalCacheByTag($tag);
        }

        parent::flushByTags($tags);
    }

    private function clearExternalCacheByTag(string $tag): void
    {
        $pageId = $this->getByTag($tag);

        if ($pageId > 0) {
            $this->getEventDispatcher()->dispatch(
                new ClearMyraCloudCacheEvent(Typo3CacheType::PAGE, (string)$pageId),
            );
        }
    }

    private function getByTag(string $tag): ?int
    {
        $cacheIdentifier = $this->getConnection()->select(['identifier'], $this->tagsTable, ['tag' => $tag])->fetchOne();

        if (!is_string($cacheIdentifier)) {
            return null;
        }

        return (int)$this->get($cacheIdentifier);
    }

    private function getConnection(): Connection
    {
        return $this->connection ??= GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->cacheTable);
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
