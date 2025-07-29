<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File;

class FileAdmin extends File
{
    public const PREFIX = '/fileadmin';

    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        return self::PREFIX;
    }
}
