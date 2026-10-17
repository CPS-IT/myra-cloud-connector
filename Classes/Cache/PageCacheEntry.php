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

/**
 * A cached page as stored in the Myra cache: the page and the language it was rendered in,
 * so a tag flush clears "/en/…" as well, not only the default language.
 *
 * Stored as "<pageId>:<languageId>". Entries written by versions up to 3.3 hold the page id
 * only; they stay valid, without a language. Versions up to 3.3 read the new format as the
 * page id ((int)"5:1" is 5), so a downgrade keeps working.
 *
 * @internal
 */
final readonly class PageCacheEntry
{
    private const PATTERN = '/^(\d+)(?::(\d+))?$/';

    public function __construct(
        public int $pageId,
        public ?int $languageId = null,
    ) {}

    public static function fromString(string $value): ?self
    {
        if (preg_match(self::PATTERN, $value, $matches) !== 1 || (int)$matches[1] <= 0) {
            return null;
        }

        return new self((int)$matches[1], isset($matches[2]) ? (int)$matches[2] : null);
    }

    public static function isValid(mixed $value): bool
    {
        return (is_string($value) || is_int($value)) && self::fromString((string)$value) !== null;
    }

    public function toString(): string
    {
        return $this->languageId === null ? (string)$this->pageId : $this->pageId . ':' . $this->languageId;
    }
}
