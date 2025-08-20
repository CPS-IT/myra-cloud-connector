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

namespace CPSIT\MyraCloudConnector\Traits;

use TYPO3\CMS\Core\Utility\GeneralUtility;

trait DomainListParserTrait
{
    /**
     * @return list<non-empty-string>
     */
    protected function parseCommaList(string $list): array
    {
        $rawList = GeneralUtility::trimExplode(',', $list, true);

        return array_values(array_unique($rawList));
    }
}
