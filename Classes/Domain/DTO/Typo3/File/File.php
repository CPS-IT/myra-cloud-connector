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

namespace CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File;

use TYPO3\CMS\Core\Resource\ResourceInterface;

final class File extends AbstractFile
{
    public function __construct(
        private readonly ResourceInterface $resource,
    ) {
        parent::__construct($this->resource->getIdentifier());
    }

    protected function getPrefix(): string
    {
        $publicFile = (string)$this->resource->getStorage()->getPublicUrl($this->resource);

        return '/' . trim(str_replace($this->getRawSlug(), '', $publicFile), '/');
    }

    public function getCombinedIdentifier(): string
    {
        if ($this->resource instanceof \TYPO3\CMS\Core\Resource\AbstractFile) {
            return $this->resource->getCombinedIdentifier();
        }

        return $this->resource->getStorage()->getUid() . ':' . $this->resource->getIdentifier();
    }
}
