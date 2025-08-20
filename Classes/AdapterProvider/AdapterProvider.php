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
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class AdapterProvider
{
    /**
     * @var array<class-string<AdapterInterface>, ProviderItem>
     */
    private static array $providerItemCache = [];

    /**
     * @param AdapterInterface[] $adapters
     */
    public function __construct(
        #[AutowireIterator('myra_cloud.external.cache.adapter')]
        private readonly iterable $adapters,
    ) {}

    /**
     * @return AdapterInterface[]
     */
    public function getAllProviderItems(): array
    {
        return iterator_to_array($this->adapters);
    }

    public function getDefaultProviderItem(): ?ProviderItemRegisterInterface
    {
        return $this->getProviderItem($this->getAllProviderItems()[0] ?? null);
    }

    public function getProviderItem(?AdapterInterface $adapter): ?ProviderItem
    {
        return $adapter ? self::$providerItemCache[$adapter::class] ??= new ProviderItem($adapter) : null;
    }
}
