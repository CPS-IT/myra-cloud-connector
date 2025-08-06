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

namespace CPSIT\MyraCloudConnector\Domain\Repository;

use CPSIT\MyraCloudConnector\Domain\DTO\Typo3\File\FileAdmin;
use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SingletonInterface;

readonly class FileRepository implements SingletonInterface
{
    public function __construct(
        private ConnectionPool $connectionPool
    ) {}

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable('sys_file_processedfile');
    }

    /**
     * @param FileInterface $file
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getProcessedFilesFromFile(FileInterface $file): array
    {
        $uid = (int)$file->getProperty('uid');
        if ($uid <= 0) {
            return [];
        }

        $qb = $this->getQueryBuilder();
        $qb->select('identifier');
        $qb->from('sys_file_processedfile');
        $qb->where(
            $qb->expr()->eq('original', $qb->createNamedParameter($uid, ParameterType::INTEGER))
        );

        $files = [];
        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
            if (!empty($row['identifier'])) {
                $files[] = new FileAdmin($row['identifier']);
            }
        }

        return $files;
    }
}
