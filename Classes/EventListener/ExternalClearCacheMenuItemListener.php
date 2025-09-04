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

namespace CPSIT\MyraCloudConnector\EventListener;

use CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use TYPO3\CMS\Backend\Backend\Event\ModifyClearCacheActionsEvent;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener('cpsit/myra-cloud-connector/external-clear-cache-menu-item')]
final readonly class ExternalClearCacheMenuItemListener
{
    public function __construct(
        private AdapterProvider $provider,
        private UriBuilder $uriBuilder,
    ) {}

    /**
     * @throws RouteNotFoundException
     */
    public function __invoke(ModifyClearCacheActionsEvent $event): void
    {
        $this->addClearAllCacheButton($event);
        $this->addClearAllResourcesCacheButton($event);
    }

    /**
     * @throws RouteNotFoundException
     */
    private function addClearAllCacheButton(ModifyClearCacheActionsEvent $event): void
    {
        $provider = $this->provider->getDefaultProviderItem();

        if ($provider && $provider->canInteract()) {
            /** @var non-empty-string $targetUrl */
            $targetUrl = (string)$this->uriBuilder->buildUriFromRoute(
                'ajax_external_cache_clear',
                [
                    'type' => Typo3CacheType::ALL_PAGE->value,
                    'id' => '-1',
                    'language' => '-1',
                ],
            );

            $event->addCacheActionIdentifier($provider->getCacheId());
            $event->addCacheAction([
                'id' => $provider->getCacheId(),
                'title' => $provider->getCacheTitle(),
                'description' => $provider->getCacheDescription(),
                'href' => $targetUrl,
                'iconIdentifier' => $provider->getCacheIconIdentifier(),
            ]);
        }
    }

    /**
     * @throws RouteNotFoundException
     */
    private function addClearAllResourcesCacheButton(ModifyClearCacheActionsEvent $event): void
    {
        $provider = $this->provider->getDefaultProviderItem();

        if ($provider && $provider->canInteract()) {
            $id = $provider->getCacheId() . '_resources';
            /** @var non-empty-string $targetUrl */
            $targetUrl = (string)$this->uriBuilder->buildUriFromRoute(
                'ajax_external_cache_clear',
                ['type' => Typo3CacheType::ALL_RESOURCES->value, 'id' => '-1'],
            );

            $event->addCacheActionIdentifier($id);
            $event->addCacheAction([
                'id' => $id,
                'title' => $provider->getCacheTitle() . '.resource',
                'description' => $provider->getCacheDescription() . '.resource',
                'href' => $targetUrl,
                'iconIdentifier' => $provider->getCacheIconIdentifier(),
            ]);
        }
    }
}
