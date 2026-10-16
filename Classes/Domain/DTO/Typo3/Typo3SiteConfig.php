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
use TYPO3\CMS\Core\Site\Entity\Site;

class Typo3SiteConfig implements SiteConfigInterface, SiteConfigHostListInterface
{
    use DomainListParserTrait;

    /**
     * @var list<non-empty-string>|null
     */
    private ?array $myraDomainList = null;

    public function __construct(
        private readonly Site $site
    ) {}

    /**
     * @return list<non-empty-string>
     */
    private function getDomainList(): array
    {
        if ($this->myraDomainList !== null) {
            return $this->myraDomainList;
        }

        $domainListString = $this->site->getConfiguration()['myra_host'] ?? '';
        $domainList = $this->parseCommaList($domainListString);

        return $this->myraDomainList = $domainList;
    }

    public function getExternalIdentifierList(): array
    {
        return $this->getDomainList();
    }

    /**
     * The site's base (with the base variant of the current context already applied)
     * and every language base; relative language bases resolve to the site's host.
     */
    public function getHostList(): array
    {
        $hosts = [$this->site->getBase()->getHost()];
        foreach ($this->site->getAllLanguages() as $language) {
            $hosts[] = $language->getBase()->getHost();
        }

        return array_values(array_unique(array_filter(array_map(strtolower(...), $hosts))));
    }
}
