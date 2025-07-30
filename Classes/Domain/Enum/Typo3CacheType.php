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

namespace CPSIT\MyraCloudConnector\Domain\Enum;

enum Typo3CacheType: int
{
    case INVALID = -1;
    case UNKNOWN = 0;
    case PAGE = 1;
    case RESOURCE = 2;
    case ALL_PAGE = 30;
    case ALL_RESOURCES = 60;
}
