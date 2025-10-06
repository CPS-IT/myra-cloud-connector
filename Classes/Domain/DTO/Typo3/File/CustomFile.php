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

final class CustomFile extends AbstractFile
{
    public function __construct(
        string $slug,
        private readonly string $prefix = '/',
    ) {
        parent::__construct($slug);
    }

    protected function getPrefix(): string
    {
        return '/' . trim($this->prefix, '/');
    }
}
