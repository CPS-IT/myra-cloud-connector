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

namespace CPSIT\MyraCloudConnector\Domain\DTO\Typo3;

use CPSIT\MyraCloudConnector\Traits\DomainListParserTrait;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

class Typo3SiteConfig implements SiteConfigInterface
{
    use DomainListParserTrait;

    /**
     * @var list<non-empty-string>|null
     */
    private ?array $myraDomainList = null;

    public function __construct(
        private readonly SiteInterface $site
    ) {}

    /**
     * @return list<non-empty-string>
     */
    private function getDomainList(): array
    {
        if ($this->myraDomainList !== null) {
            return $this->myraDomainList;
        }

        $domainList = [];
        if (method_exists($this->site, 'getConfiguration')) {
            $domainListString = $this->site->getConfiguration()['myra_host'] ?? '';
            $domainList = $this->parseCommaList($domainListString);
        }

        return $this->myraDomainList = $domainList;
    }

    public function getExternalIdentifierList(): array
    {
        return $this->getDomainList();
    }
}
