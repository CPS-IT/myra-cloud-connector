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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

final class ExternalClearCacheFileListListener
{
    private ?bool $supported = null;

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly AdapterProvider $provider,
    ) {}

    public function __invoke(ProcessFileListActionsEvent $event): void
    {
        if (!$this->isSupported()) {
            return;
        }

        $provider = $this->provider->getDefaultProviderItem();

        if ($provider && $provider->canInteract()) {
            $fileOrFolderObject = $event->getResource();

            // Add clear cache icon for file resource
            if ($fileOrFolderObject instanceof AbstractFile) {
                $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                    $provider->getJavaScriptModuleInstruction(),
                );

                $clearCacheButton = GeneralUtility::makeInstance(LinkButton::class)
                    ->setIcon($this->iconFactory->getIcon($provider->getCacheIconIdentifier(), Icon::SIZE_SMALL))
                    ->setTitle($this->getLanguageService()->sL($provider->getCacheTitle()))
                    ->setHref('#')
                    ->setClasses($provider->getTypo3CssClass() . ' dropdown-item')
                    ->setDataAttributes([
                        'id' => $fileOrFolderObject->getCombinedIdentifier(),
                        'type' => Typo3CacheType::RESOURCE->value,
                    ])
                ;

                $actionItems = [];
                $itemAdded = false;
                $itemIdentifier = 'myraCloudConnectorClearFileCache';

                foreach ($event->getActionItems() as $identifier => $actionItem) {
                    if ($identifier === 'replace') {
                        $actionItems[$itemIdentifier] = $clearCacheButton;
                        $itemAdded = true;
                    }

                    $actionItems[$identifier] = $actionItem;
                }

                if (!$itemAdded) {
                    $actionItems[$itemIdentifier] = $clearCacheButton;
                }

                $event->setActionItems($actionItems);
            }
        }
    }

    private function isSupported(): bool
    {
        if ($this->supported !== null) {
            return $this->supported;
        }

        /** @var Route|null $route */
        $route = $this->getRequest()->getAttribute('route');

        return $this->supported = $route?->getPath() === '/module/file/list';
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
    }
}
