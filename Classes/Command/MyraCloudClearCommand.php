<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "cps_myra_cloud".
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

namespace CPSIT\CpsMyraCloud\Command;

use CPSIT\CpsMyraCloud\Domain\Enum\Typo3CacheType;
use CPSIT\CpsMyraCloud\Service\ExternalCacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MyraCloudClearCommand extends Command
{
    private array $typeMap = [
        'all' => Typo3CacheType::ALL_PAGE,
        'allresources' => Typo3CacheType::ALL_RESOURCES,
        'page' => Typo3CacheType::PAGE,
        'resource' => Typo3CacheType::RESOURCE,
    ];

    private ExternalCacheService $externalCacheService;

    /**
     * @param ExternalCacheService $externalCacheService
     */
    public function __construct(ExternalCacheService $externalCacheService)
    {
        $this->externalCacheService = $externalCacheService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addUsage('myracloud:clear -t page -i [PAGE_UID like: 123]');
        $this->addUsage('myracloud:clear -t all');
        $this->addUsage('myracloud:clear -t resource -i [PATH like: /fileadmin/path/To/Directory]');
        $this->addUsage('myracloud:clear -t resource -i [PATH like: /assets/myCustomAssets/myScript.js]');
        $this->addUsage('myracloud:clear -t resource -i [PATH like: /fileadmin/path/ToFile.jpg]');
        $this->addUsage('myracloud:clear -t allresources');

        $this->setHelp('resource and allresources are always cleared recursive' . LF .
            'identifier for recursive can be a folder or a file' . LF . LF .
            '-t page ' . "\t\t" . ' require a page id' . LF .
            '-t resource ' . "\t\t" . ' require a uri. example: -t resource -i /fileadmin/user_upload/pdfs' . LF .
            '-t all ' . "\t\t" . ' clear everything in myracloud for this TYPO3 Instance (does not need a identifier)' . LF .
            '-t allresources ' . "\t" . ' clear everything, recursive, under these folders (does not need a identifier): ' . LF .
            "\t\t\t" . ' /fileadmin/*, /typo3/*, /typo3temp/*, /typo3conf/*' . LF);
        $this->addOption('type', 't', InputArgument::OPTIONAL, 'types: ' . implode(', ', array_keys($this->typeMap)), '');
        $this->addOption('identifier', 'i', InputArgument::OPTIONAL, 'page id or resource path for (page / resource type)', '');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = trim((string)$input->getOption('type'));
        $identifier = trim((string)$input->getOption('identifier'));
        $typeId = $this->typeMap[$type] ?? Typo3CacheType::INVALID;

        if ($typeId > Typo3CacheType::UNKNOWN) {
            $result = ($this->externalCacheService->clear($typeId, $identifier) ? Command::SUCCESS : Command::FAILURE);

            if ($result === Command::FAILURE) {
                $output->writeln('<error>some or all operations failed</error>');
            }

            return $result;
        }

        $output->writeln('<error>invalid options provided</error>');

        return Command::INVALID;
    }
}
