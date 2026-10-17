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

namespace CPSIT\MyraCloudConnector\Domain\DTO\Myra;

use Myracloud\WebApi\Auth\Authentication;
use Myracloud\WebApi\Auth\BearerToken;
use Myracloud\WebApi\Auth\HmacSignature;
use Myracloud\WebApi\V2;

/**
 * The extension settings as a Myra API v2 connection.
 *
 * Only API v2 is used. Both credential types work with it, so the settings of an API v1
 * setup (endpoint "api.myracloud.com", key and secret) keep working unchanged.
 *
 * Which credentials: the ones extension versions up to 3.3 / 4.1 used — the token with the
 * endpoint "apiv2.myracloud.com", key and secret with any other endpoint. Only if those are
 * incomplete, the other ones. That way a stale MYRA_TOKEN in the environment of an
 * existing v1 setup does not replace its working key and secret.
 *
 * @internal
 */
final readonly class ClientConfiguration
{
    /**
     * The hosts the endpoint setting has named so far: both stand for Myra's API.
     */
    private const MYRA_HOSTS = ['api.myracloud.com', 'apiv2.myracloud.com'];
    private const LEGACY_V2_HOST = 'apiv2.myracloud.com';

    private function __construct(
        public bool $usesToken,
        public string $baseUri,
        private Authentication $authentication,
    ) {}

    /**
     * @param string $endpoint host or URL; empty and Myra's hosts mean Myra's API v2
     * @return self|null null without complete credentials
     */
    public static function fromSettings(string $endpoint, string $apiKey, string $secret, string $token): ?self
    {
        $hasToken = $token !== '';
        $hasKeyAndSecret = $apiKey !== '' && $secret !== '';
        $prefersToken = self::hostOf($endpoint) === self::LEGACY_V2_HOST;

        if ($hasToken && ($prefersToken || !$hasKeyAndSecret)) {
            return new self(true, self::baseUriOf($endpoint), new BearerToken($token));
        }

        if ($hasKeyAndSecret) {
            return new self(false, self::baseUriOf($endpoint), new HmacSignature($apiKey, $secret));
        }

        return null;
    }

    public function createClient(): V2\Client
    {
        return new V2\Client($this->authentication, $this->baseUri);
    }

    /**
     * Myra's own hosts ("api.myracloud.com" was the API v1 default) become the v2 default;
     * anything else is a base URI of its own, e.g. a proxy or an emulator.
     */
    private static function baseUriOf(string $endpoint): string
    {
        $endpoint = trim($endpoint);

        if ($endpoint === '' || in_array(self::hostOf($endpoint), self::MYRA_HOSTS, true)) {
            return V2\Client::DEFAULT_BASE_URI;
        }

        return rtrim(self::withScheme($endpoint), '/');
    }

    private static function hostOf(string $endpoint): string
    {
        $endpoint = trim($endpoint);

        if ($endpoint === '') {
            return '';
        }

        return strtolower((string)parse_url(self::withScheme($endpoint), PHP_URL_HOST));
    }

    /**
     * Up to 3.3 / 4.1 the setting was a bare host name, "https://" was added to it.
     */
    private static function withScheme(string $endpoint): string
    {
        return str_contains($endpoint, '://') ? $endpoint : 'https://' . $endpoint;
    }
}
