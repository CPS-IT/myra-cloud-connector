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

namespace CPSIT\MyraCloudConnector\ContextMenu;

use CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Service\PageService;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;

class ExternalClearCacheContextMenuItemProvider extends AbstractProvider
{
    public function __construct(
        private readonly AdapterProvider $adapterProvider,
        private readonly PageService $pageService
    ) {
        parent::__construct();
    }

    public function canHandle(): bool
    {
        $type = $this->getCacheType();

        if (!$type->isKnown()) {
            return false;
        }

        $provider = $this->adapterProvider->getDefaultProviderItem();

        if ($provider?->canInteract() !== true) {
            return false;
        }

        if ($type === Typo3CacheType::PAGE) {
            return $this->pageService->getPage((int)$this->getIdentifier()) !== null;
        }

        if ($type === Typo3CacheType::RESOURCE) {
            return !empty($this->getIdentifier());
        }

        return false;
    }

    protected function getIdentifier(): string
    {
        $id = $this->identifier;
        $type = $this->getCacheType();

        if ($type === Typo3CacheType::PAGE && is_numeric($id)) {
            return $id;
        }

        // Enable clear cache action for folders only
        // (clear cache for files is already available in file list actions dropdown)
        if ($type === Typo3CacheType::RESOURCE && str_ends_with($id, '/')) {
            return $id;
        }

        return '';
    }

    public function getPriority(): int
    {
        return 10;
    }

    /**
     * @return array{data-callback-module?: string}
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $provider = $this->adapterProvider->getDefaultProviderItem();

        if ($provider) {
            return [
                'data-callback-module' => $provider->getJavaScriptModule(),
            ];
        }

        return [];
    }

    public function addItems(array $items): array
    {
        $this->initDisabledItems();

        return $items + $this->prepareItems($this->setupItem());
    }

    /**
     * @return array<string, array{type: string, label: string, iconIdentifier: string, callbackAction: string}>
     */
    private function setupItem(): array
    {
        $provider = $this->adapterProvider->getDefaultProviderItem();

        if ($provider !== null) {
            $this->itemsConfiguration = [
                $provider->getCacheId() => [
                    'type' => 'item',
                    'label' => $provider->getCacheTitle(),
                    'iconIdentifier' => $provider->getCacheIconIdentifier(),
                    'callbackAction' => 'clearPageViaContextMenu',
                ],
            ];
        }

        return $this->itemsConfiguration;
    }

    private function getCacheType(): Typo3CacheType
    {
        return match ($this->table) {
            'pages' => Typo3CacheType::PAGE,
            'sys_file', 'sys_file_storage' => Typo3CacheType::RESOURCE,
            default => Typo3CacheType::INVALID,
        };
    }

    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }

        $provider = $this->adapterProvider->getDefaultProviderItem();

        return $itemName === $provider?->getCacheId();
    }
}
