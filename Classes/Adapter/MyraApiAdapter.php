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

use CPSIT\MyraCloudConnector\Domain\DTO\Myra\ClientConfiguration;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\File;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\FileInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\PageSlugInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\SiteConfigHostListInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\SiteConfigInterface;
use CPSIT\MyraCloudConnector\Extension;
use Myracloud\WebApi\Exception\MyraException;
use Myracloud\WebApi\Exception\TransportException;
use Myracloud\WebApi\V2;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;

/**
 * Clears the Myra Cloud cache of TYPO3 sites through API v2 (cpsit/myra-web-api).
 *
 * The site setting "myra_host" names the Myra domains (zones); within them only the hosts
 * the site is served from are cleared.
 */
#[AutoconfigureTag('myra_cloud.external.cache.adapter')]
class MyraApiAdapter extends BaseAdapter implements BatchingAdapterInterface
{
    private const CONFIG_NAME_API_KEY = 'myra_api_key';
    private const CONFIG_NAME_ENDPOINT = 'myra_endpoint';
    private const CONFIG_NAME_SECRET = 'myra_secret';
    private const CONFIG_NAME_TOKEN = 'myra_token';

    /**
     * Clears already sent in this request: one save often clears the same page several times.
     *
     * @var array<string, true>
     */
    private array $sentClears = [];

    /**
     * Resources per request: Myra documents no limit; this keeps request bodies bounded,
     * and a rejected entry fails only its own chunk.
     */
    private const MAX_RESOURCES_PER_REQUEST = 100;

    private ?V2\Client $client = null;

    /**
     * Set by the first network error (timeout, connection, 5xx): the requests after it are
     * skipped. A save clearing many pages would otherwise wait for the timeout once per page.
     */
    private bool $unreachable = false;

    private int $batchDepth = 0;

    /**
     * Clears waiting for the end of the outermost batch: domain → host → recursive → resources.
     *
     * @var array<string, array<string, array<int, array<string, true>>>>
     */
    private array $queue = [];

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        #[Autowire('@cache.runtime')]
        private readonly FrontendInterface $cache,
        private readonly LoggerInterface $logger,
        ?Context $context = null,
    ) {
        parent::__construct($extensionConfiguration, $context);
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
     * The endpoint and the credentials in use: the token, or the API key and the secret
     * ({@see ClientConfiguration::fromSettings()}). An empty endpoint keeps the extension
     * inactive as it always did — setups use it as off switch, e.g. an unset ENV variable.
     */
    protected function getRequiredConfigKeys(array $config): array
    {
        $credentials = $this->getClientConfiguration($config)?->usesToken === true
            ? [self::CONFIG_NAME_TOKEN]
            : [self::CONFIG_NAME_API_KEY, self::CONFIG_NAME_SECRET];

        return [self::CONFIG_NAME_ENDPOINT, ...$credentials];
    }

    public function clearCache(SiteConfigInterface $site, ?PageSlugInterface $pageSlug = null, bool $recursive = false): bool
    {
        if (!$this->canExecute()) {
            return false;
        }

        $resource = $this->getResource($pageSlug, $recursive);
        $cleared = false;

        foreach ($site->getExternalIdentifierList() as $domain) {
            foreach ($this->getHostsToClear($site, $domain) as $fqdn) {
                $cleared = $this->clearCacheDomain($domain, $fqdn, $resource, $recursive) || $cleared;
            }
        }

        return $cleared;
    }

    /**
     * Myra matches `resource` as a pattern, `recursive` only extends the match to
     * subdirectories (API docs, PUT /domain/{domainId}/cache-clear): "/fileadmin"
     * matches paths ending in "/fileadmin", "/fileadmin/*" everything below it. So a
     * recursive clear of a folder or the root needs the wildcard, a single file not:
     * "/fileadmin/a.pdf/*" would no longer match the file itself.
     */
    private function getResource(?PageSlugInterface $pageSlug, bool $recursive): string
    {
        // A site base without trailing slash gives its root page an empty path.
        $resource = '/' . ltrim($pageSlug?->getSlug() ?? '', '/');

        if ($recursive && !$this->isSingleFile($pageSlug)) {
            $resource = rtrim($resource, '/') . '/*';
        }

        return $resource;
    }

    private function isSingleFile(?PageSlugInterface $slug): bool
    {
        if ($slug instanceof File) {
            return !$slug->isFolder();
        }

        // Paths FAL does not know (CLI `resource` type): a dot in the last segment means a file.
        return $slug instanceof FileInterface
            && $slug->getRawSlug() !== ''
            && str_contains(basename(rtrim($slug->getSlug(), '/')), '.');
    }

    /**
     * @return list<string>
     */
    private function getHostsToClear(SiteConfigInterface $site, string $domain): array
    {
        $hosts = $this->getActiveHosts($domain);

        if (!$site instanceof SiteConfigHostListInterface) {
            return $hosts;
        }

        // Only the hosts this site is served from: the Myra domain also carries
        // other sites and environments, which a purge here must not reach.
        $siteHosts = $site->getHostList();
        $hosts = array_values(array_filter($hosts, static fn(string $fqdn): bool => in_array($fqdn, $siteHosts, true)));

        if ($hosts === []) {
            $this->writeLog(
                'MYRA_CLOUD: no active record of domain %s matches the hosts of this site (%s), nothing was cleared',
                [$domain, implode(', ', $siteHosts)]
            );
        }

        return $hosts;
    }

    public function batch(\Closure $operation): bool
    {
        $this->batchDepth++;

        try {
            $operation();
        } finally {
            if (--$this->batchDepth === 0) {
                $sent = $this->sendQueue();
            }
        }

        return $sent ?? true;
    }

    /**
     * Sends the clear, or queues it inside a {@see batch()}.
     */
    protected function clearCacheDomain(string $domain, string $fqdn, string $path = '/', bool $recursive = false): bool
    {
        if (isset($this->sentClears[$this->getClearKey($domain, $fqdn, $path, $recursive)])) {
            return true;
        }

        if ($this->batchDepth > 0) {
            $this->queue[$domain][$fqdn][(int)$recursive][$path] = true;

            return true;
        }

        return $this->send($domain, $fqdn, [$path], $recursive);
    }

    private function sendQueue(): bool
    {
        $queue = $this->queue;
        $this->queue = [];
        $success = true;

        foreach ($queue as $domain => $hosts) {
            foreach ($hosts as $fqdn => $byRecursive) {
                foreach ($byRecursive as $recursive => $resources) {
                    foreach (array_chunk(array_keys($resources), self::MAX_RESOURCES_PER_REQUEST) as $chunk) {
                        $success = $this->send((string)$domain, (string)$fqdn, $chunk, $recursive === 1) && $success;
                    }
                }
            }
        }

        return $success;
    }

    /**
     * One request for the resources of one host: a single clear, or a bulk clear.
     *
     * @param non-empty-list<string> $resources
     */
    private function send(string $domain, string $fqdn, array $resources, bool $recursive): bool
    {
        // Without a client (Myra unreachable earlier in this request) the request is
        // skipped, but still written to sys_log as not cleared.
        $client = $this->getClient();
        $success = false;

        try {
            if ($client !== null) {
                if (count($resources) === 1) {
                    $client->cacheClear()->clear($domain, $fqdn, $resources[0], $recursive);
                } else {
                    $client->cacheClear()->clearBulk($domain, $fqdn, $resources, $recursive);
                }
                $success = true;
            }
        } catch (MyraException $exception) {
            $this->logger->error(
                'Myra Cloud cache clear of {fqdn} {resources} (domain {domain}, recursive: {recursive}) failed: {message}' . $this->noteIfUnreachable($exception),
                [
                    'domain' => $domain,
                    'fqdn' => $fqdn,
                    'resources' => implode(', ', $resources),
                    'recursive' => $recursive ? 'true' : 'false',
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ],
            );
        }

        $backendUser = $this->getBackendUser();

        foreach ($resources as $path) {
            if ($success) {
                $this->sentClears[$this->getClearKey($domain, $fqdn, $path, $recursive)] = true;
            }

            // One entry per resource, as before bulk clears.
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
        }

        return $success;
    }

    private function getClearKey(string $domain, string $fqdn, string $path, bool $recursive): string
    {
        return implode("\0", [$domain, $fqdn, $path, $recursive ? '1' : '0']);
    }

    /**
     * The hosts Myra serves for a domain: the names of its active and enabled DNS records.
     *
     * @return list<string>
     */
    private function getActiveHosts(string $domain): array
    {
        $cacheIdentifier = 'myra-cloud-api-adapter-hosts-' . md5($domain);
        $hosts = $this->cache->get($cacheIdentifier);

        if (is_array($hosts)) {
            return array_values(array_filter($hosts, is_string(...)));
        }

        $client = $this->getClient();
        if ($client === null) {
            return [];
        }

        try {
            $records = $client->dnsRecords()->list($domain);
        } catch (MyraException $exception) {
            // Not cached: an empty list after a failed request would block every
            // purge of this domain for the rest of the request.
            $this->logger->error(
                'Myra Cloud DNS records of domain {domain} could not be read: {message}' . $this->noteIfUnreachable($exception),
                ['domain' => $domain, 'message' => $exception->getMessage(), 'exception' => $exception],
            );

            return [];
        }

        $hosts = [];
        foreach ($records as $record) {
            $name = rtrim(strtolower($record->name()), '.');
            if ($record->isActive() && $record->isEnabled() && $name !== '') {
                $hosts[$name] = $name;
            }
        }
        $hosts = array_values($hosts);

        $this->cache->set($cacheIdentifier, $hosts);

        return $hosts;
    }

    private function getClient(): ?V2\Client
    {
        if ($this->unreachable) {
            return null;
        }

        return $this->client ??= $this->getClientConfiguration($this->getAdapterConfig())?->createClient();
    }

    /**
     * Marks Myra unreachable after a network error; the note goes into the log message.
     */
    private function noteIfUnreachable(MyraException $exception): string
    {
        if (!$exception instanceof TransportException) {
            return '';
        }

        $this->unreachable = true;

        return ' — Myra is not reachable, the following Myra requests of this request are skipped';
    }

    /**
     * @param array<string, mixed> $config settings as {@see getAdapterConfig()} returns them, already resolved
     */
    private function getClientConfiguration(array $config): ?ClientConfiguration
    {
        $setting = static fn(string $name): string => is_scalar($config[$name] ?? null) ? (string)$config[$name] : '';

        return ClientConfiguration::fromSettings(
            $setting(self::CONFIG_NAME_ENDPOINT),
            $setting(self::CONFIG_NAME_API_KEY),
            $setting(self::CONFIG_NAME_SECRET),
            $setting(self::CONFIG_NAME_TOKEN),
        );
    }
}
