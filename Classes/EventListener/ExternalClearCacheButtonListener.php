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
use CPSIT\MyraCloudConnector\Service\PageService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Filelist\Controller\FileListController;

#[AsEventListener('cpsit/myra-cloud-connector/external-clear-cache-button')]
final class ExternalClearCacheButtonListener
{
    private Typo3CacheType $cacheTypeCache = Typo3CacheType::UNKNOWN;
    private string $cacheId = '';

    public function __construct(
        private readonly PageService $pageService,
        private readonly IconFactory $iconFactory,
        private readonly AdapterProvider $provider,
        private readonly PageRenderer $pageRenderer,
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        if (!$this->isModuleSupported() || !$this->isPageTypeSupported()) {
            return;
        }

        $buttons = $event->getButtons();
        $provider = $this->provider->getDefaultProviderItem();

        if ($provider && $provider->canInteract()) {
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                $provider->getJavaScriptModuleInstruction(),
            );

            $clearCacheButton = $event->getButtonBar()->makeLinkButton()
                ->setIcon($this->iconFactory->getIcon($provider->getCacheIconIdentifier(), IconSize::SMALL))
                ->setTitle($this->getLanguageService()->sL($provider->getCacheTitle()))
                ->setHref('#')
                ->setClasses($provider->getTypo3CssClass())
                ->setDataAttributes([
                    'id' => $this->getIdentifier(),
                    'type' => $this->getCacheType()->value,
                ])
            ;

            if (!isset($buttons[ButtonBar::BUTTON_POSITION_RIGHT])) {
                $buttons[ButtonBar::BUTTON_POSITION_RIGHT] = [];
            }

            $buttons[ButtonBar::BUTTON_POSITION_RIGHT][1][] = $clearCacheButton;

            $event->setButtons($buttons);
        }
    }

    private function getIdentifier(): string
    {
        if ($this->cacheId !== '') {
            return $this->cacheId;
        }

        $id = (string)($this->getRequest()->getQueryParams()['id'] ?? '');

        if ($this->getCacheType() === Typo3CacheType::PAGE) {
            if (!is_numeric($id)) {
                return '';
            }

            return $this->cacheId = $id;
        }

        if ($this->getCacheType() === Typo3CacheType::RESOURCE) {
            if ($id === '') {
                $fileStorage = $this->getFirstAvailableFileStorage();
                $id = $fileStorage?->getRootLevelFolder()->getCombinedIdentifier() ?? '1:/';
            }

            return $this->cacheId = $id;
        }

        return $this->cacheId = '';
    }

    /**
     * @see FileListController::handleRequest
     */
    private function getFirstAvailableFileStorage(): ?ResourceStorage
    {
        $fileStorages = array_filter(
            $this->getBackendUser()->getFileStorages(),
            static fn(ResourceStorage $storage) => $storage->isBrowsable(),
        );

        return reset($fileStorages) ?: null;
    }

    private function isPageTypeSupported(): bool
    {
        if ($this->getCacheType() === Typo3CacheType::PAGE) {
            $pageUid = (int)$this->getIdentifier();

            if ($pageUid <= 0) {
                return false;
            }

            return $this->pageService->getPage($pageUid) !== null;
        }

        if ($this->getCacheType() === Typo3CacheType::RESOURCE) {
            $path = $this->getIdentifier();
            return !empty($path);
        }

        return false;
    }

    private function isModuleSupported(): bool
    {
        return $this->getCacheType()->isKnown();
    }

    private function getCacheType(): Typo3CacheType
    {
        if ($this->cacheTypeCache !== Typo3CacheType::UNKNOWN) {
            return $this->cacheTypeCache;
        }

        $route = $this->getBackendRoute();

        return $this->cacheTypeCache = match ($route?->getPath()) {
            '/module/file/FilelistList' => Typo3CacheType::RESOURCE,
            '/module/web/layout', '/module/web/list', '/module/web/ViewpageView' => Typo3CacheType::PAGE,
            default => Typo3CacheType::INVALID,
        };
    }

    private function getBackendRoute(): ?Route
    {
        return $this->getRequest()->getAttribute('route');
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
    }
}
