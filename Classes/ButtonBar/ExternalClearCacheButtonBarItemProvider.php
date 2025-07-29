<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "cps_myra_cloud".
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

namespace CPSIT\CpsMyraCloud\ButtonBar;

use CPSIT\CpsMyraCloud\AdapterProvider\AdapterProvider;
use CPSIT\CpsMyraCloud\Domain\Enum\Typo3CacheType;
use CPSIT\CpsMyraCloud\Service\PageService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;

class ExternalClearCacheButtonBarItemProvider
{
    private Typo3CacheType $cacheTypeCache = Typo3CacheType::UNKNOWN;
    private string $cacheId = '';

    public function __construct(
        private readonly PageService $pageService,
        private readonly IconFactory $iconFactory,
        private readonly AdapterProvider $provider
    ) {}

    /**
     * Get buttons
     *
     * @param array $params
     * @param ButtonBar $buttonBar
     * @return array
     */
    public function clearPageCache(array $params, ButtonBar $buttonBar): array
    {
        $buttons = $params['buttons'];
        if (!$this->isSupported()) {
            return $buttons;
        }

        $provider = $this->provider->getDefaultProviderItem();
        if ($provider && $provider->canInteract()) {
            $lang = $this->getLanguageService();
            $clearCacheButton = $buttonBar->makeLinkButton();
            $clearCacheButton->setIcon($this->iconFactory->getIcon($provider->getCacheIconIdentifier(), Icon::SIZE_SMALL));
            $clearCacheButton->setTitle($lang->sL($provider->getCacheTitle()))
                ->setHref('#')
                ->setClasses($provider->getTypo3CssClass())
                ->setOnClick($provider->getRequireJsCall($this->getIdentifier(), $this->getCacheType()));

            if (!isset($buttons[ButtonBar::BUTTON_POSITION_RIGHT])) {
                $buttons[ButtonBar::BUTTON_POSITION_RIGHT] = [];
            }

            $buttons[ButtonBar::BUTTON_POSITION_RIGHT][1][] = $clearCacheButton;
        }

        return $buttons;
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return string
     */
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
                $id = '1:/';
            }
            return $this->cacheId = $id;
        }

        return $this->cacheId = '';
    }

    /**
     * @return bool
     */
    private function isSupported(): bool
    {
        return
            $this->isModuleSupported() &&
            $this->isPageTypeSupported();
    }

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
    private function isModuleSupported(): bool
    {
        return $this->getCacheType() > Typo3CacheType::UNKNOWN;
    }

    private function getCacheType(): Typo3CacheType
    {
        if ($this->cacheTypeCache !== Typo3CacheType::UNKNOWN) {
            return $this->cacheTypeCache;
        }

        $route = $this->getBackendRoute();
        if ($route === '/module/file/FilelistList') {
            return $this->cacheTypeCache = Typo3CacheType::RESOURCE;
        }
        if (in_array($route, [
            '/module/web/layout',
            '/module/web/list',
            '/module/web/ViewpageView',
        ])) {
            return $this->cacheTypeCache = Typo3CacheType::PAGE;
        }

        return $this->cacheTypeCache = Typo3CacheType::INVALID;
    }

    /**
     * @return string
     */
    private function getBackendRoute(): string
    {
        return $this->getRequest()->getQueryParams()['route'] ?? '';
    }

    /**
     * @return string
     */
    private function getBackendEditTableName(): string
    {
        if (array_key_exists('sys_file_metadata', $this->getRequest()->getQueryParams()['edit'])) {
            return 'sys_file_metadata';
        }
        return '';
    }

    /**
     * @return ServerRequestInterface
     */
    private function getRequest(): ServerRequestInterface
    {
        /** @var ServerRequest $request */
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        return $request;
    }
}
