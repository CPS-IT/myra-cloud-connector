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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\SysLog\Action\Cache as SystemLogCacheAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class BaseAdapter implements SingletonInterface, AdapterInterface
{
    use DomainListParserTrait;

    /**
     * All settings, environment references resolved; null until read.
     *
     * @var array<string, string>|null
     */
    private ?array $config = null;

    /**
     * @var array<string, bool>
     */
    private array $checkupCache = [];

    private readonly Context $context;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        ?Context $context = null,
    ) {
        // Optional: adapters extending this class pass only the extension configuration.
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
    }

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
                $backendUser = $this->context->getAspect('backend.user');

                if (!$backendUser->isLoggedIn()) {
                    return false;
                }

                $allConfigData = $this->getAdapterConfig(true);
                $onlyAdmin = (int)($allConfigData['onlyAdmin'] ?? 1) === 1;

                return !$onlyAdmin || $backendUser->isAdmin();
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

    /**
     * Settings that must not be empty for the adapter to run. Default: every setting
     * with the adapter's prefix.
     *
     * @param array<string, mixed> $config
     * @return list<string>
     */
    protected function getRequiredConfigKeys(array $config): array
    {
        $prefix = $this->getAdapterConfigPrefix();

        return array_values(array_filter(array_map(strval(...), array_keys($config)), static fn(string $key): bool => str_starts_with($key, $prefix)));
    }

    private function setupConfigCondition(): bool
    {
        return $this->getFromCache(
            __METHOD__,
            function () {
                $allConfigData = $this->getAdapterConfig(true);
                foreach ($this->getRequiredConfigKeys($allConfigData) as $key) {
                    if (empty($this->getRealAdapterConfigValue($allConfigData[$key] ?? ''))) {
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
        return $this->checkupCache[$identifier] ??= $buildFunction();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAdapterConfig(bool $ignorePrefix = false): array
    {
        if ($this->config === null) {
            try {
                $data = $this->extensionConfiguration->get(Extension::KEY);
            } catch (\Exception) {
                $data = [];
            }

            $this->config = [];
            foreach (is_array($data) ? $data : [] as $key => $value) {
                $this->config[(string)$key] = $this->getRealAdapterConfigValue($value);
            }
        }

        if ($ignorePrefix) {
            return $this->config;
        }

        $prefix = $this->getAdapterConfigPrefix();

        return array_filter($this->config, static fn(string $key): bool => str_starts_with($key, $prefix), ARRAY_FILTER_USE_KEY);
    }

    protected function getServerRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
    }

    protected function getRealAdapterConfigValue(mixed $value): string
    {
        // The extension settings form stores strings, but configuration from files
        // (settings.php, YAML loaders) arrives typed: `onlyLive: 0` is an int.
        $value = match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => (string)$value,
            default => '',
        };

        if (stripos($value, 'ENV=') === 0) {
            return (string)getenv(substr($value, 4));
        }

        return $value;
    }
}
