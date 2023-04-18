<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__ . '/src']);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
    $rectorConfig->symfonyContainerXml(__DIR__ . '/var/cache/dev/SolidInvoice_KernelDevDebugContainer.xml');
    $rectorConfig->phpVersion(PhpVersion::PHP_74);

    $rectorConfig->sets([
        // General
        SetList::CODE_QUALITY,

        // PHP
        LevelSetList::UP_TO_PHP_74,

        // PHPUnit
        PHPUnitSetList::PHPUNIT_70,
        PHPUnitSetList::PHPUNIT_80,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_91,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_EXCEPTION,
        PHPUnitSetList::PHPUNIT_YIELD_DATA_PROVIDER,

        // Doctrine
        DoctrineSetList::DOCTRINE_25,
        DoctrineSetList::DOCTRINE_COMMON_20,
        DoctrineSetList::DOCTRINE_DBAL_30,
        DoctrineSetList::DOCTRINE_DBAL_40,
        DoctrineSetList::DOCTRINE_ORM_29,
        DoctrineSetList::DOCTRINE_ORM_213,
        DoctrineSetList::DOCTRINE_BEHAVIORS_20,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,

        // DoctrineSetList::DOCTRINE_REPOSITORY_AS_SERVICE,

        // Symfony
        SymfonySetList::SYMFONY_40,
        SymfonySetList::SYMFONY_41,
        SymfonySetList::SYMFONY_42,
        SymfonySetList::SYMFONY_43,
        SymfonySetList::SYMFONY_44,
        SymfonySetList::SYMFONY_50,
        SymfonySetList::SYMFONY_51,
        SymfonySetList::SYMFONY_52,
        SymfonySetList::SYMFONY_53,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_50_TYPES,
        SymfonySetList::SYMFONY_52_VALIDATOR_ATTRIBUTES,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);
};
