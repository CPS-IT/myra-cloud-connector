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
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

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

    public function getJavaScriptModule(): string
    {
        return $this->getAdapter()->getJavaScriptModule();
    }

    public function getJavaScriptModuleInstruction(): JavaScriptModuleInstruction
    {
        return $this->getAdapter()->getJavaScriptModuleInstruction();
    }

    public function getJavaScriptMethod(): string
    {
        return $this->getAdapter()->getJavaScriptMethod();
    }

    public function getTypo3CssClass(): string
    {
        return 't3js-clear-myra-cache';
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
