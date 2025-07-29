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

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3;

class Page implements PageInterface
{
    public function __construct(
        private readonly int $uid,
        private readonly string $title,
        private readonly bool $hidden,
        private readonly int $dokType,
        private readonly string $slug,
    ) {}

    public function getPageId(): int
    {
        return $this->uid;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDokType(): int
    {
        return $this->dokType;
    }

    public function getVisibility(): bool
    {
        return $this->hidden;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
