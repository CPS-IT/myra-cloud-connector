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

namespace CPSIT\MyraCloudConnector\AdapterProvider;

use CPSIT\MyraCloudConnector\Adapter\AdapterInterface;
use CPSIT\MyraCloudConnector\Domain\DTO\Provider\ProviderItem;
use CPSIT\MyraCloudConnector\Domain\DTO\Provider\ProviderItemRegisterInterface;

final class AdapterProvider
{
    private static array $providerItemCache = [];

    /**
     * @param AdapterInterface[] $adapters
     */
    public function __construct(
        private readonly iterable $adapters
    ) {}

    public function getAllProviderItems(): iterable
    {
        return iterator_to_array($this->adapters);
    }

    /**
     * @return ProviderItemRegisterInterface|null
     */
    public function getDefaultProviderItem(): ?ProviderItemRegisterInterface
    {
        return $this->getProviderItem($this->getAllProviderItems()[0] ?? null);
    }

    /**
     * @param AdapterInterface|null $adapter
     * @return ProviderItemRegisterInterface|null
     */
    public function getProviderItem(?AdapterInterface $adapter): ?ProviderItemRegisterInterface
    {
        return $adapter ? self::$providerItemCache[$adapter::class] ??= new ProviderItem($adapter) : null;
    }
}
