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

namespace CPSIT\MyraCloudConnector\EventListener;

use CPSIT\MyraCloudConnector\Cache\MyraCacheFrontend;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;

#[AsEventListener('cpsit/myra-cloud-connector/pages-cache')]
final readonly class PagesCacheListener
{
    public function __construct(
        #[Autowire('@cache.myracloud')]
        private MyraCacheFrontend $myraCache,
    ) {}

    public function __invoke(AfterCachedPageIsPersistedEvent $event): void
    {
        $cacheTags = $event->getCacheData()['cacheTags'];
        $pageId = $this->determinePageIdFromCacheTags($cacheTags);

        if ($pageId !== null) {
            $this->myraCache->set($event->getCacheIdentifier(), $pageId, $cacheTags);
        }
    }

    /**
     * @param list<string> $cacheTags
     */
    private function determinePageIdFromCacheTags(array $cacheTags): ?int
    {
        foreach ($cacheTags as $cacheTag) {
            if (MathUtility::canBeInterpretedAsInteger($cacheTag)) {
                return (int)$cacheTag;
            }

            if (str_starts_with($cacheTag, 'pageId_')) {
                return (int)substr($cacheTag, strlen('pageId_'));
            }
        }

        return null;
    }
}
