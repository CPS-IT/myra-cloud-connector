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

namespace CPSIT\MyraCloudConnector\DataHandler;

use CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider;
use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Service\ExternalCacheService;
use CPSIT\MyraCloudConnector\Service\PageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * @internal
 */
#[Autoconfigure(public: true, shared: true)]
final readonly class DataHandlerHook
{
    public function __construct(
        private ExternalCacheService $externalCacheService,
        private AdapterProvider $provider,
        private PageService $pageService,
        private TcaSchemaFactory $tcaSchemaFactory,
        #[Autowire('@cache.runtime')]
        private FrontendInterface $runtimeCache,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array{table: string, uid: int, uid_page: int}|array{cacheCmd: string, tags: list<string>} $data
     */
    public function clearCachePostProc(array $data): void
    {
        // Early return on unsupported hook call
        if (!isset($data['uid'], $data['table'], $data['uid_page'])) {
            return;
        }

        $provider = $this->provider->getDefaultProviderItem();

        // Early return if provider is not automated
        if ($provider?->canAutomated() !== true) {
            return;
        }

        $tableName = $data['table'];
        $recordUid = (int)$data['uid'];

        [$pageUid, $languageId] = match ($tableName) {
            'pages' => $this->resolveParametersForPage($recordUid),
            default => $this->resolveParametersForRecord($tableName, $recordUid, $data['uid_page']),
        };

        try {
            $cacheIdentifier = 'MyraCloudConnector_DataHandlerHook_' . $pageUid . '_' . $languageId;

            if ($pageUid !== null && $this->runtimeCache->get($cacheIdentifier) === false) {
                $result = $this->externalCacheService->clear(Typo3CacheType::PAGE, (string)$pageUid, $languageId);

                $this->runtimeCache->set($cacheIdentifier, $result);
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Unable to clear MyraCloud cache for incoming record change {table}:{uid}, resolving to page {pageUid} with language {languageId}: {message}',
                [
                    'table' => $tableName,
                    'uid' => $recordUid,
                    'pageUid' => $pageUid,
                    'languageId' => $languageId,
                    'message' => $exception->getMessage(),
                ],
            );
        }
    }

    /**
     * @return array{int|null, int}
     */
    private function resolveParametersForPage(int $recordUid): array
    {
        $page = $this->pageService->getPage($recordUid);
        $pageUid = $page?->getLanguageId() > 0 ? $page->getTranslationSource() : $page?->getPageId();
        $languageId = $page?->getLanguageId() ?? 0;

        return [$pageUid, $languageId];
    }

    /**
     * @return array{int|null, int}
     */
    private function resolveParametersForRecord(string $tableName, int $recordUid, int $pageUid): array
    {
        $record = BackendUtility::getRecord($tableName, $recordUid);
        $tcaSchema = $this->tcaSchemaFactory->get($tableName);
        $languageField = $tcaSchema->isLanguageAware() ? $tcaSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName() : null;
        $languageId = $languageField !== null ? ($record[$languageField] ?? 0) : 0;

        return [$pageUid, $languageId];
    }
}
