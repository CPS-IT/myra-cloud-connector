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

use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\PageSlugInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\SiteConfigInterface;
use CPSIT\MyraCloudConnector\Extension;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Myracloud\WebApi\Endpoint\CacheClear;
use Myracloud\WebApi\Endpoint\CacheClearV2;
use Myracloud\WebApi\WebApi;
use Myracloud\WebApi\WebApiV2;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * @phpstan-type DnsRecordVO array{
 *     objectType: string,
 *     id: int,
 *     modified: string,
 *     created?: string,
 *     name: string,
 *     ttl?: int,
 *     recordType: string,
 *     alternativeCname?: string,
 *     active?: bool,
 *     value: string,
 *     priority?: int,
 *     paused?: bool,
 *     upstreamOptions?: array{
 *         weight?: int,
 *         maxFails?: int,
 *         failTimeout?: int,
 *         backup?: bool,
 *         down?: bool,
 *     },
 *     caaTag?: string,
 *     caaFlags?: int,
 *     endpoints?: list<string>,
 *     serviceType?: int,
 *     enabled?: bool,
 *     port?: int,
 *     weight?: int,
 * }
 */
#[AutoconfigureTag('myra_cloud.external.cache.adapter')]
class MyraApiAdapter extends BaseAdapter
{
    /**
     * @var array<string, bool>
     */
    private static array $multiClearCacheProtection = [];

    /**
     * @var array<string, WebApi|WebApiV2>
     */
    protected array $clients = [];

    private const CONFIG_NAME_API_KEY = 'myra_api_key';
    private const CONFIG_NAME_ENDPOINT = 'myra_endpoint';
    private const CONFIG_NAME_SECRET = 'myra_secret';
    private const CONFIG_NAME_TOKEN = 'myra_token';

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        #[Autowire('@cache.runtime')]
        private readonly FrontendInterface $cache,
    ) {
        parent::__construct($extensionConfiguration);
    }

    public function getCacheId(): string
    {
        return Extension::KEY;
    }

    public function getCacheIconIdentifier(): string
    {
        return 'ext-myra-cloud-connector-myra';
    }

    public function getCacheTitle(): string
    {
        return 'LLL:EXT:myra_cloud_connector/Resources/Private/Language/locallang_myra.xlf:title';
    }

    public function getCacheDescription(): string
    {
        return 'LLL:EXT:myra_cloud_connector/Resources/Private/Language/locallang_myra.xlf:description';
    }

    protected function getAdapterConfigPrefix(): string
    {
        return 'myra';
    }

    /**
     * @param SiteConfigInterface $site
     * @param PageSlugInterface|null $pageSlug
     * @param bool $recursive
     * @return bool
     */
    public function clearCache(SiteConfigInterface $site, ?PageSlugInterface $pageSlug = null, bool $recursive = false): bool
    {
        if (!$this->canExecute()) {
            return false;
        }

        $r = false;
        // if no slug provided / clear root
        $slug = ($pageSlug !== null) ? $pageSlug->getSlug() : '/';
        foreach ($site->getExternalIdentifierList() as $domain) {
            foreach ($this->getFqdnForSite($domain) as $subDomain) {
                $r |= $this->clearCacheDomain($domain, $subDomain, $slug, $recursive);
            }
        }

        return (bool)$r;
    }

    /**
     * @param string $siteRef
     * @param string $fqdn
     * @param string $path
     * @param bool $recursive
     * @return string
     */
    protected function getSendHash(string $siteRef, string $fqdn, string $path, bool $recursive = false): string
    {
        return md5($siteRef . '_' . $fqdn . '_' . $path . '_' . $recursive);
    }

    /**
     * @param string $domain
     * @param string $fqdn
     * @param string $path
     * @param bool $recursive
     * @return bool
     */
    protected function clearCacheDomain(string $domain, string $fqdn, string $path = '/', bool $recursive = false): bool
    {
        $domainIdentifier = $this->getDomainIdentifier($domain);
        if ($domainIdentifier === null) {
            return false;
        }
        $hash = $this->getSendHash($domain, $fqdn, $path, $recursive);
        if ((self::$multiClearCacheProtection[$hash] ?? false) === true) {
            return true;
        }

        try {
            $r = $this->getCacheClearApi()->clear($domainIdentifier, $fqdn, $path, $recursive);
            self::$multiClearCacheProtection[$hash] = $success = (!empty($r) && ($r['error'] ?? true) === false);
        } catch (GuzzleException) {
            return false;
        }

        $backendUser = $this->getBackendUser();

        $this->writeLog(
            'User %s has cleared the MYRA_CLOUD cache for domain %s => %s%s (recursive: %s) (success: %s)',
            [
                ($backendUser?->user['username'] ?? '') . ' (uid: ' . ($backendUser?->user['uid'] ?? 0) . ')',
                $domain,
                $fqdn,
                $path,
                ($recursive ? 'true' : 'false'),
                ($success ? 'true' : 'false'),
            ]
        );

        return $success;
    }

    /**
     * @return string[]
     */
    private function getFqdnForSite(string $domainIdentifier): array
    {
        $cacheIdentifier = 'myra-cloud-api-adapter-fqdn-site-' . crc32($domainIdentifier);

        if (($fqdn = $this->cache->get($cacheIdentifier)) === false) {
            $r = $this->getDomainRecordsForDomain($domainIdentifier);
            $fqdn = [];

            if (!empty($r) && $r['error'] === false) {
                foreach ($r['data'] ?? $r['list'] ?? [] as $recordItem) {
                    $name = $recordItem['name'];
                    $active = (bool)($recordItem['active'] ?? false);
                    $enable = (bool)($recordItem['enabled'] ?? false);
                    if ($active && $enable && $name !== '') {
                        $fqdn[crc32((string)$name)] = $name;
                    }
                }
            }

            $this->cache->set($cacheIdentifier, $fqdn);
        }

        return $fqdn;
    }

    /**
     * @return array{}|array{
     *     error: bool,
     *     data?: list<DnsRecordVO>,
     *     list?: list<DnsRecordVO>,
     *     page: int,
     *     count: int,
     *     pageSize: int
     * }
     */
    private function getDomainRecordsForDomain(string $domain): array
    {
        try {
            $domainId = $this->getDomainIdentifier($domain);

            if ($domainId === null) {
                return [];
            }

            return $this->getMyraClient()->getDnsRecordEndpoint()->getList($domainId);
        } catch (\Exception|GuzzleException) {
        }

        return [];
    }

    private function getCacheClearApi(): CacheClear|CacheClearV2
    {
        return $this->getMyraClient()->getCacheClearEndpoint();
    }

    private function getDomainIdentifier(string $domain): int|string|null
    {
        $cacheIdentifier = 'myra-cloud-api-domain-identifier-' . crc32($domain);

        if (($domainIdentifier = $this->cache->get($cacheIdentifier)) === false) {
            $client = $this->getMyraClient();
            $domainIdentifier = null;

            if ($client instanceof WebApiV2) {
                try {
                    $domains = $client->getDomainEndpoint()->getList();

                    foreach ($domains['data'] ?? [] as $domainResult) {
                        if ($domainResult['name'] === $domain) {
                            $domainIdentifier = $domainResult['id'];
                            break;
                        }
                    }
                } catch (GuzzleException) {
                }
            } else {
                $domainIdentifier = $domain;
            }

            $this->cache->set($cacheIdentifier, $domainIdentifier);
        }

        return $domainIdentifier;
    }

    private function getMyraClient(): WebApi|WebApiV2
    {
        $config = $this->getAdapterConfig();
        $instanceId = md5(serialize($config));

        if (isset($this->clients[$instanceId])) {
            return $this->clients[$instanceId];
        }

        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        $isV2 = WebApiV2::isV2($config[self::CONFIG_NAME_ENDPOINT]) && $config[self::CONFIG_NAME_TOKEN] !== '';

        if ($isV2) {
            $api = new WebApiV2($config[self::CONFIG_NAME_TOKEN], $config[self::CONFIG_NAME_ENDPOINT]);
        } else {
            $api = new WebApi($config[self::CONFIG_NAME_API_KEY], $config[self::CONFIG_NAME_SECRET], $config[self::CONFIG_NAME_ENDPOINT]);
        }

        return $this->clients[$instanceId] = $api;
    }
}
