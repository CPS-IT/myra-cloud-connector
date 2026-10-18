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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
        private ConnectionPool $connectionPool,
    ) {}

    public function getPageWithUid(int $pageUid, ?int $languageId = null): ?PageInterface
    {
        try {
            $page = $this->getLivePage($pageUid, $languageId);
        } catch (SiteNotFoundException) {
            // A deleted page has no root line, so no site either.
            $page = null;
        }

        return $page ?? $this->getDeletedPage($pageUid, $languageId);
    }

    private function getLivePage(int $pageUid, ?int $languageId): ?PageInterface
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
        // to 3.3 / 4.1 an untranslated page was cleared as the site root "/" instead.
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
     * A deleted page, under the URL it had. Neither the site lookup nor the router sees deleted
     * pages, and deleting a page tree deletes all of its pages before TYPO3 clears their caches:
     * the root line and the URL are built from the deleted records. Nothing for a page that was
     * not public when it was deleted (hidden, start or end time): it was not served.
     */
    private function getDeletedPage(int $pageUid, ?int $languageId): ?PageInterface
    {
        $row = BackendUtility::getRecord('pages', $pageUid, '*', '', false);
        if ($row === null || !(bool)$row['deleted']) {
            return null;
        }

        if ((int)$row['l10n_parent'] > 0) {
            // A translation's own uid, as a record change passes it: that translation.
            $translation = $row;
            $languageId = (int)$translation['sys_language_uid'];
            $row = BackendUtility::getRecord('pages', (int)$translation['l10n_parent'], '*', '', false);
        } else {
            $languageId = (int)$languageId;
            $translation = $languageId > 0 ? $this->getTranslationRecord($pageUid, $languageId) : null;
        }

        if ($row === null || !$this->wasPublic($row) || ($translation !== null && !$this->wasPublic($translation))) {
            return null;
        }

        $defaultUid = (int)$row['uid'];
        // The router takes a complete page record as it is (see PageRouter::generateUri()).
        $route = match (true) {
            $translation !== null => array_replace($translation, ['uid' => $defaultUid, '_LOCALIZED_UID' => (int)$translation['uid']]),
            // Without a translation, the default page under the language's URL, as for a live page.
            $languageId > 0 => array_replace($row, ['sys_language_uid' => $languageId]),
            default => $row,
        };

        try {
            $site = $this->siteFinder->getSiteByPageId($defaultUid, $this->getRootLineIncludingDeleted($defaultUid));
            $slug = $site->getRouter()
                ->generateUri($route, ['_language' => $site->getLanguageById($languageId)])
                ->getPath()
            ;
        } catch (SiteNotFoundException|\InvalidArgumentException) {
            return null;
        }

        return new Page(
            uid: $translation !== null ? (int)$translation['uid'] : $defaultUid,
            title: (string)($translation ?? $row)['title'],
            hidden: false,
            dokType: (int)$row['doktype'],
            slug: $slug,
            language: $languageId,
            translationSource: $translation !== null ? $defaultUid : null,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getTranslationRecord(int $pageUid, int $languageId): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            // The latest, should the page have been translated more than once.
            ->orderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative()
        ;

        return $row === false ? null : $row;
    }

    /**
     * The root line of a page, deleted pages included, enough to find its site
     * ({@see \TYPO3\CMS\Core\Site\SiteFinder::getSiteByPageId()}): the root line TYPO3 builds
     * ends at a deleted page.
     *
     * @return list<array{uid: int}>
     */
    public function getRootLineIncludingDeleted(int $pageUid): array
    {
        $rootLine = [];
        $row = BackendUtility::getRecord('pages', $pageUid, 'uid,pid', '', false);

        // Bounded: a broken pid chain must not loop.
        for ($depth = 0; $row !== null && $depth < 100; $depth++) {
            $rootLine[] = ['uid' => (int)$row['uid']];
            $pid = (int)$row['pid'];
            $row = $pid > 0 ? BackendUtility::getRecord('pages', $pid, 'uid,pid', '', false) : null;
        }

        return $rootLine;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function wasPublic(array $row): bool
    {
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');

        return !(bool)$row['hidden']
            && ((int)$row['starttime'] === 0 || (int)$row['starttime'] <= $now)
            && ((int)$row['endtime'] === 0 || (int)$row['endtime'] > $now);
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
