<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3;

interface SiteConfigExternalIdentifierInterface
{
    public function getExternalIdentifierList(): array;
}
