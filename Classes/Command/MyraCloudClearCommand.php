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

namespace CPSIT\MyraCloudConnector\Command;

use CPSIT\MyraCloudConnector\Domain\Enum\Typo3CacheType;
use CPSIT\MyraCloudConnector\Service\ExternalCacheService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MyraCloudClearCommand extends Command
{
    public function __construct(
        private readonly ExternalCacheService $externalCacheService,
    ) {
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
            "\t\t\t" . ' /fileadmin/*, /typo3/*, /typo3temp/*, /_assets/*' . LF);
        $this->addOption('type', 't', InputArgument::OPTIONAL, 'types: ' . implode(', ', Typo3CacheType::names()), '');
        $this->addOption('identifier', 'i', InputArgument::OPTIONAL, 'page id or resource path for (page / resource type)', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = trim((string)$input->getOption('type'));
        $identifier = trim((string)$input->getOption('identifier'));
        $typeId = Typo3CacheType::tryFromName($type) ?? Typo3CacheType::INVALID;

        if (!$typeId->isKnown()) {
            $io->error('Invalid options provided.');

            return self::INVALID;
        }

        if (!$this->externalCacheService->clear($typeId, $identifier)) {
            $io->error('Some or all operations failed.');

            return self::FAILURE;
        }

        $io->success('Cache clear request was successful.');

        return self::SUCCESS;
    }
}
