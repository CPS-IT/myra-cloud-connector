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

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $configCache = [];

    /**
     * @var array<string, bool>
     */
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

    /**
     * @param array<string, mixed>|array<int, mixed> $arguments
     */
    protected function writeLog(string $message, array $arguments): void
    {
        $beUser = $this->getBackendUser();
        $beUser?->writeLog(
            SystemLogType::CACHE,
            SystemLogCacheAction::CLEAR,
            SystemLogErrorClassification::MESSAGE,
            null,
            $message,
            $arguments,
        );
    }

    public function canExecute(): bool
    {
        return $this->setupConfigCondition() && $this->liveOnlyCondition() && !$this->isDomainBlacklisted();
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
        return $this->getFromCache(
            __METHOD__,
            function () {
                $allConfigData = $this->getAdapterConfig(true);
                $hooksDisabled = (int)($allConfigData['disableHooks'] ?? 0) === 1;

                return !$hooksDisabled;
            },
        );
    }

    private function adminOnlyCondition(): bool
    {
        return $this->getFromCache(
            __METHOD__,
            function () {
                $backendUser = $this->getBackendUser();

                if ($backendUser === null) {
                    return false;
                }

                $allConfigData = $this->getAdapterConfig(true);
                $onlyAdmin = (int)($allConfigData['onlyAdmin'] ?? 1) === 1;

                if ($onlyAdmin) {
                    return $backendUser->isAdmin();
                }

                return true;
            },
        );
    }

    private function liveOnlyCondition(): bool
    {
        return $this->getFromCache(
            __METHOD__,
            function () {
                $allConfigData = $this->getAdapterConfig(true);
                $only = (int)($allConfigData['onlyLive'] ?? 1) === 1;

                if ($only) {
                    return Environment::getContext()->isProduction();
                }

                return true;
            },
        );
    }

    private function isDomainBlacklisted(): bool
    {
        return $this->getFromCache(
            __METHOD__,
            function () {
                if (Environment::isCli()) {
                    return false;
                }

                $blacklistString = $this->getAdapterConfig(true)['domainBlacklist'] ?? '';
                $blackList = $this->parseCommaList($blacklistString);
                $request = $this->getServerRequest();
                $currentDomainContext = $request->getUri()->getHost();
                $isBlacklisted = false;

                foreach ($blackList as $pattern) {
                    if ($pattern === $currentDomainContext || fnmatch($pattern, $currentDomainContext)) {
                        $isBlacklisted = true;
                        break;
                    }
                }

                return $isBlacklisted;
            },
        );
    }

    private function setupConfigCondition(): bool
    {
        return $this->getFromCache(
            __METHOD__,
            function () {
                $allConfigData = $this->getAdapterConfig(true);
                foreach ($allConfigData as $key => $value) {
                    if (empty($this->getRealAdapterConfigValue($value)) && str_starts_with($key, $this->getAdapterConfigPrefix())) {
                        return false;
                    }
                }

                return true;
            },
        );
    }

    /**
     * @param \Closure(): bool $buildFunction
     */
    private function getFromCache(string $identifier, \Closure $buildFunction): bool
    {
        return self::$checkupCache[$identifier] ?? (self::$checkupCache[$identifier] = $buildFunction());
    }

    /**
     * @return array<string, mixed>
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
