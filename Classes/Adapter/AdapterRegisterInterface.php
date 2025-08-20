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

namespace CPSIT\MyraCloudConnector\Adapter;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

interface AdapterRegisterInterface
{
    /**
     * @return non-empty-string
     */
    public function getCacheId(): string;

    /**
     * @return non-empty-string
     */
    public function getCacheIconIdentifier(): string;

    /**
     * @return non-empty-string
     */
    public function getCacheTitle(): string;

    /**
     * @return non-empty-string
     */
    public function getCacheDescription(): string;

    /**
     * @return non-empty-string
     */
    public function getJavaScriptModule(): string;

    public function getJavaScriptModuleInstruction(): JavaScriptModuleInstruction;

    /**
     * @return non-empty-string
     */
    public function getJavaScriptMethod(): string;
}
