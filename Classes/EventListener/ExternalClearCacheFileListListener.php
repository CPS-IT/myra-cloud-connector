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
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

#[AsEventListener('cpsit/myra-cloud-connector/external-clear-cache-file-list')]
final class ExternalClearCacheFileListListener
{
    private ?bool $supported = null;

    public function __construct(
        private readonly IconFactory $iconFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly AdapterProvider $provider,
        private readonly ComponentFactory $componentFactory,
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

                // @todo This does not work yet, needs https://review.typo3.org/c/Packages/TYPO3.CMS/+/95109
                //       to be approved and released first.
                $clearCacheButton = $this->componentFactory->createLinkButton()
                    ->setIcon($this->iconFactory->getIcon($provider->getCacheIconIdentifier(), IconSize::SMALL))
                    ->setTitle($this->getLanguageService()->sL($provider->getCacheTitle()))
                    ->setHref('#')
                    ->setClasses($provider->getTypo3CssClass())
                    ->setDataAttributes([
                        'id' => $fileOrFolderObject->getCombinedIdentifier(),
                        'type' => Typo3CacheType::RESOURCE->value,
                    ])
                ;

                $event->setAction(
                    $clearCacheButton,
                    'myraCloudConnectorClearFileCache',
                    ActionGroup::secondary,
                    'replace',
                );
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
