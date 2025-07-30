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

namespace CPSIT\MyraCloudConnector\Domain\DTO\Provider;

use CPSIT\MyraCloudConnector\Adapter\AdapterInterface;
use CPSIT\MyraCloudConnector\Adapter\AdapterRegisterInterface;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;

interface ProviderItemRegisterInterface extends AdapterRegisterInterface
{
    public function getAdapter(): AdapterInterface;

    public function getRequireJsCall(string $id, Typo3CacheType $type = Typo3CacheType::UNKNOWN): string;

    public function getTypo3CssClass(): string;

    public function canExecute(): bool;

    public function canInteract(): bool;

    public function canAutomated(): bool;
}
