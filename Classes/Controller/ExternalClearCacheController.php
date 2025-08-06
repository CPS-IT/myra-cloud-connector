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

namespace CPSIT\MyraCloudConnector\Controller;

use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Service\ExternalCacheService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true)]
readonly class ExternalClearCacheController
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ExternalCacheService $externalCacheService
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function clearPageCache(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getQueryParams()['id'] ?? '0';
        $type = Typo3CacheType::tryFrom((int)($request->getQueryParams()['type'] ?? Typo3CacheType::UNKNOWN->value));

        $result = $this->externalCacheService->clear($type, $identifier);

        return $this->getJsonResponse(['status' => $result], (!$result ? 500 : 200));
    }

    /**
     * @param array $data
     * @param int $statusCode
     * @return ResponseInterface
     */
    private function getJsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(
            (string)json_encode($data, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)
        );
        return $response;
    }
}
