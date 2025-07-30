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
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Myracloud\WebApi\Endpoint\AbstractEndpoint;
use Myracloud\WebApi\Endpoint\CacheClear;
use Myracloud\WebApi\Endpoint\DnsRecord;
use Myracloud\WebApi\Middleware\Signature;

class MyraApiAdapter extends BaseAdapter
{
    protected array $clients;
    private static array $multiClearCacheProtection = [];

    private const CONFIG_NAME_API_KEY = 'myra_api_key';
    private const CONFIG_NAME_ENDPOINT = 'myra_endpoint';
    private const CONFIG_NAME_SECRET = 'myra_secret';

    public function getCacheId(): string
    {
        return Extension::KEY;
    }

    public function getCacheIconIdentifier(): string
    {
        return 'cps-cache-myra';
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
        foreach ($site->getExternalIdentifierList() as $domainIdentifier) {
            foreach ($this->getFqdnForSite($domainIdentifier) as $subDomain) {
                $r |= $this->clearCacheDomain($domainIdentifier, $subDomain, $slug, $recursive);
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
        $hash = $this->getSendHash($domain, $fqdn, $path, $recursive);
        if ((self::$multiClearCacheProtection[$hash] ?? false) === true) {
            return true;
        }

        try {
            $r = $this->getCacheClearApi()->clear($domain, $fqdn, $path, $recursive);
            self::$multiClearCacheProtection[$hash] = $success = (!empty($r) && ($r['error'] ?? true) === false);
        } catch (GuzzleException) {
            return false;
        }

        $this->writeLog(
            'User %s has cleared the MYRA_CLOUD cache for domain %s => %s%s (recursive: %s) (success: %s)',
            [
                $this->getBEUser()->user['username'] . ' (uid: ' . $this->getBEUser()->user['uid'] . ')',
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
     * @param string $domainIdentifier
     * @return string[]
     */
    private function getFqdnForSite(string $domainIdentifier): array
    {
        // todo: caching?
        $r = $this->getDomainRecordsForDomain($domainIdentifier);
        $fqdn = [];
        if (!empty($r) && $r['error'] === false) {
            foreach ($r['list'] as $recordItem) {
                $name = $recordItem['name'] ?? '';
                $active = (bool)($recordItem['active'] ?? false);
                $enable = (bool)($recordItem['enabled'] ?? false);
                if ($active && $enable && $name !== '') {
                    $fqdn[crc32((string)$name)] = $name;
                }
            }
        }

        return array_values($fqdn);
    }

    /**
     * @param string $domain
     * @return array
     */
    private function getDomainRecordsForDomain(string $domain): array
    {
        /** @var DnsRecord $st */
        $st = $this->getEndPointApi(DnsRecord::class);
        $r = [];
        try {
            $r = $st->getList($domain);
        } catch (\Exception|GuzzleException) {
        }

        return $r;
    }

    /**
     * @return CacheClear|null
     */
    private function getCacheClearApi(): ?CacheClear
    {
        return $this->getEndPointApi(CacheClear::class);
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T the created instance
     */
    private function getEndPointApi(string $className): ?AbstractEndpoint
    {
        if (!class_exists($className)) {
            return null;
        }

        $client = $this->getMyraClient();
        try {
            return $client !== null ? new $className($client) : null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @return ClientInterface|null
     */
    private function getMyraClient(): ?ClientInterface
    {
        $config = $this->getAdapterConfig();
        $instanceId = md5(serialize($config));
        if (isset($this->clients[$instanceId])) {
            return $this->clients[$instanceId];
        }

        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());

        $signature = new Signature($config[self::CONFIG_NAME_SECRET], $config[self::CONFIG_NAME_API_KEY]);
        $stack->push(
            Middleware::mapRequest(
                $signature->signRequest(...),
            ),
        );
        return $this->clients[$instanceId] = new Client(
            [
                'base_uri' => 'https://' . $config[self::CONFIG_NAME_ENDPOINT] . '/' . 'en' . '/rapi',
                'handler'  => $stack,
            ],
        );
    }
}
