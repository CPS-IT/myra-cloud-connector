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

return [
    'directories' => [
        '.build',
        '.git',
        '.github',
        'bin',
        'build',
        'public',
        'tailor-version-upload',
        'tests',
        'vendor',
    ],
    'files' => [
        'DS_Store',
        'composer.lock',
        'composer-dependency-analyser.php',
        'crowdin.yaml',
        'docker-compose.yaml',
        'editorconfig',
        'gitattributes',
        'gitignore',
        'packaging_exclude.php',
        'php-cs-fixer.php',
        'phpstan.neon',
        'phpunit.xml',
        'rector.php',
        'renovate.json',
        'version-bumper.yaml',
    ],
];
