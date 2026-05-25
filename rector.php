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

use Rector\CodeQuality\Rector\Attribute\SortAttributeNamedArgsRector;
use Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Rector\CodeQuality\Rector\BooleanOr\RepeatedOrEqualToInArrayRector;
use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\CodeQuality\Rector\Class_\ConvertStaticToSelfRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyRegexPatternRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\FuncCall\SortCallLikeNamedArgsRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitSelfCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector;
use Rector\Symfony\Configs\Rector\Closure\ServiceSetStringNameToClassNameRector;
use Rector\Symfony\Configs\Rector\Closure\ServiceSettersToSettersAutodiscoveryRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony34\Rector\Closure\ContainerGetNameToTypeInTestsRector;
use Rector\Symfony\Symfony73\Rector\Class_\GetFunctionsToAsTwigFunctionAttributeRector;
use Rector\Transform\Rector\Attribute\AttributeKeyToClassConstFetchRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/config',
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/SolidInvoice_KernelDevDebugContainer.xml')
    ->withPhpVersion(PhpVersion::PHP_85)
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets(symfony: true, doctrine: true, gedmo: true, phpunit: true)
    ->withSets([
        // General
        SetList::CODE_QUALITY,

        // PHP
        LevelSetList::UP_TO_PHP_85,

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
    ])
    ->withRules([
        PreferPHPUnitSelfCallRector::class,
    ])
    ->withSkip([
        // GetFunctionsToAsTwigFunctionAttributeRector cannot be used yet, since it only migrates some functions
        // to twig attributes, but some twig extensions still extend the AbstractExtension which is prohibited
        GetFunctionsToAsTwigFunctionAttributeRector::class,
        ServiceSetStringNameToClassNameRector::class,
        ServiceSettersToSettersAutodiscoveryRector::class,
        ControllerMethodInjectionToConstructorRector::class => [
            // This rule moved the `Column` class from the `renderField` method to the constructor, which will break grid rendering
            'src/DataGridBundle/Twig/Components/DataGrid.php',
        ],

        // This changes fetching string service names to the class names in the container in tests, while the service might not exist and breaking tests
        ContainerGetNameToTypeInTestsRector::class,
        PreferPHPUnitThisCallRector::class, // Use PreferPHPUnitSelfCallRector instead

        // Skip for new can be added/adjusted later
        ExplicitBoolCompareRector::class,
        ConvertStaticToSelfRector::class,
        FinalizeTestCaseClassRector::class,
        SafeDeclareStrictTypesRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        SortCallLikeNamedArgsRector::class,
        DisallowedEmptyRuleFixerRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        AttributeKeyToClassConstFetchRector::class,
        InlineConstructorDefaultToPropertyRector::class,
        SortAttributeNamedArgsRector::class,
        CombineIfRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
        SimplifyIfNullableReturnRector::class,
        CleanupUnneededNullsafeOperatorRector::class,
        UnnecessaryTernaryExpressionRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        SimplifyIfReturnBoolRector::class,
        ThrowWithPreviousExceptionRector::class,
        InlineIfToExplicitIfRector::class,
        RepeatedOrEqualToInArrayRector::class,
        SingleInArrayToCompareRector::class,
        RenameClassRector::class,
        SimplifyDeMorganBinaryRector::class,
        SimplifyRegexPatternRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        SimplifyIfElseToTernaryRector::class,
    ]);
