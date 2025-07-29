<?php

declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;
use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$config->setParallelConfig(ParallelConfigFactory::detect());
$config->setHeader('This file is part of the TYPO3 CMS extension "cps_myra_cloud".');
$config->getFinder()
    ->in(__DIR__)
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
;

return $config;
