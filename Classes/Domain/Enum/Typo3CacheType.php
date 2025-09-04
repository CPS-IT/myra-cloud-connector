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

namespace CPSIT\MyraCloudConnector\Domain\Enum;

enum Typo3CacheType: int
{
    private const NAME_MAP = [
        'all' => self::ALL_PAGE,
        'allresources' => self::ALL_RESOURCES,
        'page' => self::PAGE,
        'resource' => self::RESOURCE,
    ];

    case INVALID = -1;
    case UNKNOWN = 0;
    case PAGE = 1;
    case RESOURCE = 2;
    case ALL_PAGE = 30;
    case ALL_RESOURCES = 60;

    public function isKnown(): bool
    {
        return $this->value > self::UNKNOWN->value;
    }

    public static function fromName(string $name): self
    {
        return self::NAME_MAP[\strtolower($name)]
            ?? throw new \InvalidArgumentException('Unknown cache type: ' . $name, 1754466009)
        ;
    }

    public static function tryFromName(string $name): ?self
    {
        try {
            return self::fromName($name);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return \array_keys(self::NAME_MAP);
    }

    public function name(): string
    {
        $name = \array_search($this, self::NAME_MAP, true);

        if ($name === false) {
            throw new \InvalidArgumentException('Unknown cache type: ' . $this->value, 1757497642);
        }

        return $name;
    }
}
