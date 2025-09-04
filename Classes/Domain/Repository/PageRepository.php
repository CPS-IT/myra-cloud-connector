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
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository as CorePageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

readonly class PageRepository implements SingletonInterface
{
    public function __construct(
        private SiteFinder $siteFinder,
        private CorePageRepository $pageRepository,
    ) {}

    public function getPageWithUid(int $pageUid, ?int $languageId = null): ?PageInterface
    {
        if ($languageId > 0) {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
            $siteLanguage = $site->getLanguageById($languageId);
            $languageAspect = LanguageAspectFactory::createFromSiteLanguage($siteLanguage);
            $row = $this->pageRepository->getPageOverlay($pageUid, $languageAspect);
        } else {
            $row = $this->pageRepository->getPage($pageUid);
        }

        if ($row === []) {
            return null;
        }

        $resolvedPageUid = (int)($row['_LOCALIZED_UID'] ?? $row['uid'] ?? 0);

        if ($resolvedPageUid === 0) {
            return null;
        }

        return new Page(
            uid: $resolvedPageUid,
            title: $row['title'],
            hidden: (bool)$row['hidden'],
            dokType: (int)$row['doktype'],
            slug: $this->resolvePageSlug($row, $resolvedPageUid),
            language: (int)$row['sys_language_uid'],
            translationSource: $row['l10n_parent'] !== null ? (int)$row['l10n_parent'] : null,
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function resolvePageSlug(array $result, int $pageUid): string
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($result['l10n_parent'] ?: $pageUid);
            $siteLanguage = $site->getLanguageById($result['sys_language_uid']);
        } catch (SiteNotFoundException | \InvalidArgumentException) {
            return $result['slug'];
        }

        return $site->getRouter()
            ->generateUri($pageUid, ['_language' => $siteLanguage])
            ->getPath()
        ;
    }
}
