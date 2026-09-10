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

use CPSIT\MyraCloudConnector\Exception\InvalidCacheEntry;
use CPSIT\MyraCloudConnector\Exception\InvalidCacheIdentifier;
use CPSIT\MyraCloudConnector\Exception\InvalidCacheTag;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal
 */
final class MyraCacheFrontend extends AbstractFrontend
{
    /**
     * @param string[] $tags
     * @throws InvalidCacheEntry
     * @throws InvalidCacheIdentifier
     * @throws InvalidCacheTag
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null): void
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new InvalidCacheIdentifier($entryIdentifier);
        }

        foreach ($tags as $tag) {
            if (!$this->isValidTag($tag)) {
                throw new InvalidCacheTag($tag);
            }
        }

        if (!MathUtility::canBeInterpretedAsInteger($data)) {
            throw new InvalidCacheEntry($data);
        }

        $this->backend->set($entryIdentifier, (string)(int)$data, $tags, $lifetime);
    }

    public function get($entryIdentifier): int
    {
        if (!$this->isValidEntryIdentifier($entryIdentifier)) {
            throw new InvalidCacheIdentifier($entryIdentifier);
        }

        return (int)$this->backend->get($entryIdentifier);
    }
}
