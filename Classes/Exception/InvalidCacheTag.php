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

namespace CPSIT\MyraCloudConnector\Exception;

final class InvalidCacheTag extends Exception
{
    public function __construct(string $identifier)
    {
        parent::__construct(
            sprintf('"%s" is not a valid tag for a cache entry.', $identifier),
            1788950752,
        );
    }
}
