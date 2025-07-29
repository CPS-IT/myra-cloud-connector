<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3;

class Page implements PageInterface
{
    private int $uid;
    private string $title;
    private bool $hidden;
    private int $dokType;
    private string $slug;

    /**
     * @param int $uid
     * @param string $title
     * @param bool $hidden
     * @param int $dokType
     * @param string $slug
     */
    public function __construct(int $uid, string $title, bool $hidden, int $dokType, string $slug)
    {
        $this->uid = $uid;
        $this->title = $title;
        $this->hidden = $hidden;
        $this->dokType = $dokType;
        $this->slug = $slug;
    }

    public function getPageId(): int
    {
        return $this->uid;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDokType(): int
    {
        return $this->dokType;
    }

    public function getVisibility(): bool
    {
        return $this->hidden;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
