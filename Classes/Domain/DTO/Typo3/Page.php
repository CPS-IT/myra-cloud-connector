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

namespace CPSIT\MyraCloudConnector\Domain\DTO\Typo3;

readonly class Page implements PageInterface
{
    public function __construct(
        private int $uid,
        private string $title,
        private bool $hidden,
        private int $dokType,
        private string $slug,
        private int $language = 0,
        private ?int $translationSource = null,
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

    public function getLanguageId(): int
    {
        return $this->language;
    }

    public function getTranslationSource(): ?int
    {
        return $this->translationSource;
    }
}
