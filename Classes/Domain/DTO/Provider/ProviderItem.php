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
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;

class ProviderItem implements ProviderItemRegisterInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {}

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getCacheId(): string
    {
        return $this->getAdapter()->getCacheId();
    }

    public function getCacheIconIdentifier(): string
    {
        return $this->getAdapter()->getCacheIconIdentifier();
    }

    public function getCacheTitle(): string
    {
        return $this->getAdapter()->getCacheTitle();
    }

    public function getCacheDescription(): string
    {
        return $this->getAdapter()->getCacheDescription();
    }

    public function getRequireJsNamespace(): string
    {
        return $this->getAdapter()->getRequireJsNamespace();
    }

    public function getRequireJsFunction(): string
    {
        return $this->getAdapter()->getRequireJsFunction();
    }

    public function getRequireJsCall(string $id, Typo3CacheType $type = Typo3CacheType::UNKNOWN): string
    {
        return 'require(["' . $this->getRequireJsNamespace() . '"],function(c){c.' . $this->getRequireJsFunction() . '(' . $type->value . ', \'' . $id . '\');});return false;';
    }

    public function getTypo3CssClass(): string
    {
        return 't3js-clear-page-cache';
    }

    public function canExecute(): bool
    {
        return $this->getAdapter()->canExecute();
    }

    public function canInteract(): bool
    {
        return $this->getAdapter()->canInteract();
    }

    public function canAutomated(): bool
    {
        return $this->getAdapter()->canAutomated();
    }
}
