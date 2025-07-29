<?php
declare(strict_types=1);

namespace CPSIT\CpsMyraCloud\Domain\DTO\Typo3;

interface PageInterface extends
    PageIdInterface,
    PageSlugInterface,
    PageStatusInterface,
    PageTitleInterface
{

}
