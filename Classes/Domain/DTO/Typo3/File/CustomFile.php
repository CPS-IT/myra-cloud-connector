<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File;

class CustomFile extends File
{
    private string $prefix;

    /**
     * @param string $slug
     * @param string $prefix
     */
    public function __construct(string $slug, string $prefix = '/')
    {
        $this->prefix = $prefix;
        parent::__construct($slug);
    }

    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        return '/' . trim($this->prefix, '/');
    }
}
