<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File;

class Typo3Conf extends File
{
    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        return '/typo3conf';
    }
}
