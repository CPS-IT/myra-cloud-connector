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

namespace CPSIT\MyraCloudConnector\Adapter;

/**
 * An adapter that can collect cache clears and send them together.
 *
 * Saving one record can clear many pages — every page carrying one of its cache tags — and
 * each page clear arrives on its own (one ClearMyraCloudCacheEvent per page). Sent one by one,
 * that is one request per page and host inside the save; collected, it is one request per
 * host. Optional: callers use {@see \CPSIT\MyraCloudConnector\AdapterProvider\AdapterProvider::batch()},
 * which runs adapters without this interface directly.
 */
interface BatchingAdapterInterface extends AdapterInterface
{
    /**
     * Runs $operation and sends the clears it requested afterwards. Inside, clearCache()
     * returns whether the clear could be queued; whether it was sent is the result of the
     * outermost batch() — a nested call joins the outer batch and returns true. Clears
     * queued before an exception are sent nonetheless.
     *
     * @param \Closure(): mixed $operation
     * @return bool whether every queued clear was sent (true if there was none)
     */
    public function batch(\Closure $operation): bool;
}
