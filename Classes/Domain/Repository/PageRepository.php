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

namespace CPSIT\MyraCloudConnector\Domain\Repository;

use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\Page;
use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\PageInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository as CorePageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class PageRepository implements SingletonInterface
{
    public function __construct(
        private SiteFinder $siteFinder,
        private CorePageRepository $pageRepository,
        private Context $context,
    ) {}

    public function getPageWithUid(int $pageUid, ?int $languageId = null): ?PageInterface
    {
        $foundInLanguage = false;

        if ($languageId > 0) {
            // The page as the router finds it for a link in that language (see
            // PageRouter::generateUri()): translated, from the fallback chain, or untranslated.
            $languageContext = clone $this->context;
            $languageContext->setAspect(
                'language',
                LanguageAspectFactory::createFromSiteLanguage($this->siteFinder->getSiteByPageId($pageUid)->getLanguageById($languageId)),
            );
            $row = GeneralUtility::makeInstance(CorePageRepository::class, $languageContext)->getPage($pageUid);
            $foundInLanguage = $row !== [];
        }

        if (!$foundInLanguage) {
            $row = $this->pageRepository->getPage($pageUid);
        }

        if ($row === []) {
            return null;
        }

        $resolvedPageUid = (int)($row['_LOCALIZED_UID'] ?? $row['uid'] ?? 0);

        if ($resolvedPageUid === 0) {
            return null;
        }

        // A language is cleared under its own URL ("/en/…"), also without a translation: with a
        // fallback chain (e.g. "fallbacks: 0") the frontend serves the page there. If it answers
        // that URL with 404 instead (no fallback chain, l18n_cfg), clearing it is harmless. Up
        // to 3.3 an untranslated page was cleared as the site root "/" instead.
        $slug = $foundInLanguage
            ? $this->resolvePageSlug($row, $pageUid, (int)$languageId)
            : $this->resolvePageSlug($row, $resolvedPageUid, (int)$row['sys_language_uid']);

        return new Page(
            uid: $resolvedPageUid,
            title: $row['title'],
            hidden: (bool)$row['hidden'],
            dokType: (int)$row['doktype'],
            slug: $slug,
            language: (int)$row['sys_language_uid'],
            translationSource: (int)$row['l10n_parent'] > 0 ? (int)$row['l10n_parent'] : null,
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function resolvePageSlug(array $result, int $pageUid, int $languageId): string
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($result['l10n_parent'] ?: $pageUid);
            $siteLanguage = $site->getLanguageById($languageId);
        } catch (SiteNotFoundException|\InvalidArgumentException) {
            return $result['slug'];
        }

        return $site->getRouter()
            ->generateUri($pageUid, ['_language' => $siteLanguage])
            ->getPath()
        ;
    }
}
