<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Adapter;

use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\PageSlugInterface;
use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\SiteConfigInterface;

interface AdapterInterface extends AdapterRegisterInterface
{
    public function canExecute(): bool;

    public function canInteract(): bool;

    public function canAutomated(): bool;

    public function clearCache(SiteConfigInterface $site, ?PageSlugInterface $pageSlug = null, bool $recursive = false): bool;
}
