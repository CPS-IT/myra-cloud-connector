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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
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

    public function getRequireJsNamespace(): string
    {
        return 'TYPO3/CMS/MyraCloudConnector/ContextMenuActions';
    }

    public function getRequireJsFunction(): string
    {
        return 'ClearExternalCache';
    }

    abstract public function getCacheId(): string;

    abstract public function getCacheIconIdentifier(): string;

    abstract public function getCacheTitle(): string;

    abstract public function getCacheDescription(): string;

    abstract protected function getAdapterConfigPrefix(): string;

    /**
     * @return BackendUserAuthentication|null
     */
    protected function getBEUser(): ?BackendUserAuthentication
    {
        if (($GLOBALS['BE_USER'] ?? null) instanceof BackendUserAuthentication) {
            return $GLOBALS['BE_USER'];
        }

        return null;
    }

    /**
     * @param string $message
     * @param array $arguments
     */
    protected function writeLog(string $message, array $arguments): void
    {
        $beUser = $this->getBEUser();
        if ($beUser) {
            $beUser->writeLog(
                SystemLogType::CACHE,
                SystemLogCacheAction::CLEAR,
                SystemLogErrorClassification::MESSAGE,
                0,
                $message,
                $arguments
            );
        }
    }

    /**
     * @return bool
     */
    public function canExecute(): bool
    {
        return !(
            !$this->setupConfigCondition() ||
            !$this->liveOnlyCondition() ||
            !$this->domainNotBlacklisted()
        );
    }

    /**
     * @return bool
     */
    public function canInteract(): bool
    {
        return !(
            !$this->adminOnlyCondition() ||
            !$this->canExecute()
        );
    }

    /**
     * @return bool
     */
    public function canAutomated(): bool
    {
        return !(
            !$this->isAutomatedAllowedCondition() ||
            !$this->canExecute()
        );
    }

    /**
     * @return bool
     */
    private function isAutomatedAllowedCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        $allConfigData = $this->getAdapterConfig(true);
        $hooksDisabled = ($allConfigData['disableHooks'] ?? '0') === '1';

        return self::$checkupCache[__METHOD__] = !$hooksDisabled;
    }

    /**
     * @return bool
     */
    private function adminOnlyCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        /** @var BackendUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser) {
            return self::$checkupCache[__METHOD__] = false;
        }

        $allConfigData = $this->getAdapterConfig(true);
        $only = ($allConfigData['onlyAdmin'] ?? '1') === '1';
        if ($only) {
            return self::$checkupCache[__METHOD__] = $backendUser->isAdmin();
        }

        return self::$checkupCache[__METHOD__] = true;
    }

    /**
     * @return bool
     */
    private function liveOnlyCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        $allConfigData = $this->getAdapterConfig(true);
        $only = ($allConfigData['onlyLive'] ?? '1') === '1';
        if ($only) {
            return self::$checkupCache[__METHOD__] = Environment::getContext()->isProduction();
        }

        return self::$checkupCache[__METHOD__] = true;
    }

    /**
     * @return bool
     */
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
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        $currentDomainContext = $request->getUri()->getHost();
        return self::$checkupCache[__METHOD__] = (empty($blackList) || !in_array($currentDomainContext, $blackList));
    }

    /**
     * @return bool
     */
    private function setupConfigCondition(): bool
    {
        if (isset(self::$checkupCache[__METHOD__])) {
            return self::$checkupCache[__METHOD__];
        }

        $allConfigData = $this->getAdapterConfig(true);
        foreach ($allConfigData as $key => $value) {
            if (str_starts_with($key, $this->getAdapterConfigPrefix())) {
                if (empty($this->getRealAdapterConfigValue($value))) {
                    return self::$checkupCache[__METHOD__] = false;
                }
            }
        }

        return self::$checkupCache[__METHOD__] = true;
    }

    /**
     * @param bool $ignorePrefix
     * @return array
     */
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

        $data = [];
        try {
            $data = $this->extensionConfiguration->get(Extension::KEY);
        } catch (\Exception) {
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

    /**
     * @param string $value
     * @return string
     */
    private function getRealAdapterConfigValue(string $value): string
    {
        if (stripos($value, 'ENV=') === 0) {
            return (string)getenv(substr($value, 4));
        }

        return $value;
    }
}
