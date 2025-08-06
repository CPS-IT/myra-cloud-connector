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

use CPSIT\MyraCloudConnector\Extension;
use CPSIT\MyraCloudConnector\Traits\DomainListParserTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\SysLog\Action\Cache as SystemLogCacheAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;

abstract class BaseAdapter implements SingletonInterface, AdapterInterface
{
    use DomainListParserTrait;

    private static array $configCache = [];
    private static array $checkupCache = [];

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function getJavaScriptModule(): string
    {
        return '@cpsit/myra-cloud-connector/clear-cache-actions';
    }

    public function getJavaScriptModuleInstruction(): JavaScriptModuleInstruction
    {
        return JavaScriptModuleInstruction::create($this->getJavaScriptModule() . '.js');
    }

    public function getJavaScriptMethod(): string
    {
        return 'clearExternalCache';
    }

    abstract protected function getAdapterConfigPrefix(): string;

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;

        if ($backendUser instanceof BackendUserAuthentication) {
            return $backendUser;
        }

        return null;
    }

    protected function writeLog(string $message, array $arguments): void
    {
        $beUser = $this->getBackendUser();
        if ($beUser) {
            $beUser->writeLog(
                SystemLogType::CACHE,
                SystemLogCacheAction::CLEAR,
                SystemLogErrorClassification::MESSAGE,
                0,
                $message,
                $arguments,
            );
        }
    }

    public function canExecute(): bool
    {
        return $this->setupConfigCondition() && $this->liveOnlyCondition() && $this->domainNotBlacklisted();
    }

    public function canInteract(): bool
    {
        return $this->adminOnlyCondition() && $this->canExecute();
    }

    public function canAutomated(): bool
    {
        return $this->isAutomatedAllowedCondition() && $this->canExecute();
    }

    private function isAutomatedAllowedCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        $allConfigData = $this->getAdapterConfig(true);
        $hooksDisabled = (int)($allConfigData['disableHooks'] ?? 0) === 1;

        return self::$checkupCache[__METHOD__] = !$hooksDisabled;
    }

    private function adminOnlyCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        $backendUser = $this->getBackendUser();

        if (!$backendUser) {
            return self::$checkupCache[__METHOD__] = false;
        }

        $allConfigData = $this->getAdapterConfig(true);
        $only = (int)($allConfigData['onlyAdmin'] ?? 1) === 1;

        if ($only) {
            return self::$checkupCache[__METHOD__] = $backendUser->isAdmin();
        }

        return self::$checkupCache[__METHOD__] = true;
    }

    private function liveOnlyCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        $allConfigData = $this->getAdapterConfig(true);
        $only = (int)($allConfigData['onlyLive'] ?? 1) === 1;

        if ($only) {
            return self::$checkupCache[__METHOD__] = Environment::getContext()->isProduction();
        }

        return self::$checkupCache[__METHOD__] = true;
    }

    private function domainNotBlacklisted(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        if (Environment::isCli()) {
            return self::$checkupCache[__METHOD__] = true;
        }

        $blacklistString = $this->getAdapterConfig(true)['domainBlacklist'] ?? '';
        $blackList = $this->parseCommaList($blacklistString);
        $request = $this->getServerRequest();
        $currentDomainContext = $request->getUri()->getHost();

        return self::$checkupCache[__METHOD__] = (empty($blackList) || !in_array($currentDomainContext, $blackList, true));
    }

    private function setupConfigCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        $allConfigData = $this->getAdapterConfig(true);
        foreach ($allConfigData as $key => $value) {
            if (str_starts_with($key, $this->getAdapterConfigPrefix()) && empty($this->getRealAdapterConfigValue($value))) {
                return self::$checkupCache[__METHOD__] = false;
            }
        }

        return self::$checkupCache[__METHOD__] = true;
    }

    protected function getAdapterConfig(bool $ignorePrefix = false): array
    {
        $prefix = $this->getAdapterConfigPrefix();

        if (!empty(self::$configCache)) {
            if ($ignorePrefix) {
                return self::$configCache['all'];
            }

            if (!empty(self::$configCache[$prefix])) {
                return self::$configCache[$prefix];
            }
        }

        try {
            $data = $this->extensionConfiguration->get(Extension::KEY);
        } catch (\Exception) {
            $data = [];
        }

        foreach ($data as $key => $value) {
            $value = $this->getRealAdapterConfigValue($value);

            self::$configCache['all'][$key] = $value;

            if (str_starts_with((string)$key, $prefix)) {
                self::$configCache[$prefix][$key] = $value;
            }
        }

        if ($ignorePrefix) {
            return self::$configCache['all'];
        }

        return self::$configCache[$prefix];
    }

    protected function getServerRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
    }

    protected function getRealAdapterConfigValue(string $value): string
    {
        if (stripos($value, 'ENV=') === 0) {
            return (string)getenv(substr($value, 4));
        }

        return $value;
    }
}
