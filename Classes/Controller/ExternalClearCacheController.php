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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

#[Autoconfigure(public: true)]
readonly class ExternalClearCacheController
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ExternalCacheService $externalCacheService,
        private SiteFinder $siteFinder,
    ) {}

    public function clearPageCache(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getQueryParams()['id'] ?? '0';
        $type = Typo3CacheType::from((int)($request->getQueryParams()['type'] ?? Typo3CacheType::UNKNOWN->value));

        $languageId = $request->getQueryParams()['language'] ?? null;
        $languages = [null];

        if (is_numeric($languageId) && \in_array($type, [Typo3CacheType::PAGE, Typo3CacheType::ALL_PAGE], true)) {
            if ($languageId >= 0) {
                $languages = [(int)$languageId];
            } elseif ((int)$languageId === -1) {
                $languages = $this->getAllPageLanguages((int)$identifier);
            }
        }

        $result = 0;

        foreach ($languages as $language) {
            $result |= $this->externalCacheService->clear($type, $identifier, $language);
        }

        return $this->getJsonResponse(['status' => (bool)$result], $result ? 200 : 500);
    }

    /**
     * @param array<string, mixed> $data
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

    /**
     * @return int[]|array{null}
     */
    private function getAllPageLanguages(int $pageUid): array
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
        } catch (SiteNotFoundException) {
            return [null];
        }

        return \array_map(
            static fn(SiteLanguage $siteLanguage) => $siteLanguage->getLanguageId(),
            $site->getAvailableLanguages($this->getBackendUser()),
        );
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
