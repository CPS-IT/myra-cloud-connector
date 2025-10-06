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

namespace CPSIT\MyraCloudConnector\Service;

use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\PageInterface;
use CPSIT\MyraCloudConnector\Domain\Repository\PageRepository;

final readonly class PageService
{
    public function __construct(
        private PageRepository $pageRepository
    ) {}

    public function getPage(int $pageUid, ?int $languageId = null): ?PageInterface
    {
        if ($pageUid > 0) {
            try {
                return $this->pageRepository->getPageWithUid($pageUid, $languageId);
            } catch (\Exception) {
            }
        }

        return null;
    }
}
