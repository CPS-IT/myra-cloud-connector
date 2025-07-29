<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3\File;

abstract class File implements FileInterface
{
    private string $slug;

    abstract protected function getPrefix(): string;

    /**
     * @param string $slug
     */
    public function __construct(string $slug = '')
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getRawSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        $relPath = $this->getPrefix() . '/' . $this->getRawSlug();
        $pathSegments = array_filter(explode('/', $relPath));
        return '/'.implode('/', $pathSegments);
    }
}
