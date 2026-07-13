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

use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Doctrine\TypedCollections\Rector\ClassMethod\RemoveNewArrayCollectionOutsideConstructorRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitSelfCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector;
use Rector\Symfony\Configs\Rector\Closure\FromServicePublicToDefaultsPublicRector;
use Rector\Symfony\Configs\Rector\Closure\ServiceSetStringNameToClassNameRector;
use Rector\Symfony\Configs\Rector\Closure\ServiceSettersToSettersAutodiscoveryRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony34\Rector\Closure\ContainerGetNameToTypeInTestsRector;
use Rector\Transform\Rector\Attribute\AttributeKeyToClassConstFetchRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\ValueObject\PhpVersion;
use SolidWorx\Platform\Tools\Rector\Set\SolidWorxSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/config',
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/SolidInvoice_KernelDevDebugContainer.xml')
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets()
    ->withPhpSets()
    ->withRootFiles()
    ->withSets([
        // General
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        // SetList::DEAD_CODE,
        SetList::RECTOR_PRESET,
        // SetList::TYPE_DECLARATION,
        SetList::TYPE_DECLARATION_DOCBLOCKS,
        SetList::INSTANCEOF,
        SetList::CARBON,
        SetList::PRIVATIZATION,
        // SetList::ASSERT,

        // PHP
        LevelSetList::UP_TO_PHP_84,

        // PHPUnit
        PHPUnitSetList::PHPUNIT_70,
        PHPUnitSetList::PHPUNIT_80,
        PHPUnitSetList::PHPUNIT_90,
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::PHPUNIT_120,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,

        // Doctrine
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::TYPED_COLLECTIONS,
        DoctrineSetList::TYPED_COLLECTIONS_DOCBLOCKS,
        DoctrineSetList::YAML_TO_ANNOTATIONS,

        // Symfony
        SymfonySetList::CONFIGS,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,

        // SolidWorx Platform
        SolidWorxSetList::PLATFORM,
    ])
    ->withRules([
        PreferPHPUnitSelfCallRector::class,
    ])
    ->withSkip([
        // The secrets vault directory contains generated (gitignored) files that are
        // rewritten at runtime, e.g. by the installer and its tests.
        __DIR__ . '/config/env',

        ServiceSetStringNameToClassNameRector::class,
        ServiceSettersToSettersAutodiscoveryRector::class,
        ControllerMethodInjectionToConstructorRector::class => [
            // This rule moved the `Column` class from the `renderField` method to the constructor, which will break grid rendering
            'src/DataGridBundle/Twig/Components/DataGrid.php',
        ],

        // This changes fetching string service names to the class names in the container in tests, while the service might not exist and breaking tests
        ContainerGetNameToTypeInTestsRector::class,
        PreferPHPUnitThisCallRector::class, // Use PreferPHPUnitSelfCallRector instead

        // This makes the exception names too long and is not a meaningful changerector
        CatchExceptionNameMatchingTypeRector::class,

        DeclareStrictTypesRector::class => [
            // This file is auto-generated, which removes the strict types declare every time
            'config/reference.php',
        ],

        RemoveNewArrayCollectionOutsideConstructorRector::class => [
            // This file uses __clone() which must use a new array collection
            'src/InvoiceBundle/Entity/Invoice.php',
        ],

        FromServicePublicToDefaultsPublicRector::class => [
            // This rule removes ->public() calls on services that must be defined as public.
            'src/PaymentBundle/Resources/config/services/services.php',
        ],

        AttributeKeyToClassConstFetchRector::class => [
            'src/PaymentBundle/Entity/PaymentMethod.php',
            'src/UserBundle/Entity/ApiTokenHistory.php',
        ],

        // The Kernel re-applies Symfony's BundleAdapter wrapping that the platform kernel's
        // initializeBundles() override skips. The instanceof check must target the legacy
        // HttpKernel BundleInterface (renaming it to the DI one inverts the logic), and
        // configureContainer() must stay protected so the kernel dispatch can invoke it.
        RenameClassRector::class => [
            'src/Kernel.php',
        ],
        MakeInheritedMethodVisibilitySameAsParentRector::class => [
            'src/Kernel.php',
        ],
    ]);
