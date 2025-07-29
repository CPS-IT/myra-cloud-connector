<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File;

use CPSIT\CpsMyraCloud\Domain\DTO\Typo3\PageSlugInterface;

interface FileInterface extends PageSlugInterface
{
    public function getRawSlug(): string;
}
