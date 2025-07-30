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

namespace CPSIT\MyraCloudConnector\Adapter;

use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\PageSlugInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\SiteConfigInterface;

interface AdapterInterface extends AdapterRegisterInterface
{
    public function canExecute(): bool;

    public function canInteract(): bool;

    public function canAutomated(): bool;

    public function clearCache(SiteConfigInterface $site, ?PageSlugInterface $pageSlug = null, bool $recursive = false): bool;
}
