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
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\Identical\SimplifyBoolIdenticalTrueRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfNullableReturnRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodeQuality\Rector\NullsafeMethodCall\CleanupUnneededNullsafeOperatorRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Collection22\Rector\CriteriaOrderingConstantsDeprecationRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\AnnotationWithValueToAttributeRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\CoversAnnotationWithValueToAttributeRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\NarrowUnusedSetUpDefinedPropertyRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableArgumentRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\AddInstanceofAssertForNullableInstanceRector;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\BareCreateMockAssignToDirectUseRector;
use Rector\PHPUnit\CodeQuality\Rector\Expression\DecorateWillReturnMapWithExpectsMockRector;
use Rector\PHPUnit\CodeQuality\Rector\FuncCall\AssertFuncCallToPHPUnitAssertRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertComparisonToSpecificMethodRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEmptyNullableObjectToAssertInstanceofRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertEqualsToSameRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\AssertSameBoolNullToSpecificMethodRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\StringCastAssertStringContainsStringRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\WithCallbackIdenticalToStandaloneAssertsRector;
use Rector\PHPUnit\CodeQuality\Rector\StmtsAwareInterface\DeclareStrictTypesTestsRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\PHPUnit120\Rector\Class_\PropertyCreateMockToCreateStubRector;
use Rector\PHPUnit\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Symfony\CodeQuality\Rector\BinaryOp\ResponseStatusCodeRector;
use Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\ActionSuffixRemoverRector;
use Rector\Symfony\CodeQuality\Rector\ClassMethod\RemoveUnusedRequestParamRector;
use Rector\Symfony\CodeQuality\Rector\MethodCall\LiteralGetToRequestClassConstantRector;
use Rector\Symfony\CodeQuality\Rector\MethodCall\ParameterBagTypedGetMethodCallRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Symfony34\Rector\Closure\ContainerGetNameToTypeInTestsRector;
use Rector\Symfony\Symfony43\Rector\MethodCall\WebTestCaseAssertResponseCodeRector;
use Rector\Symfony\Symfony51\Rector\ClassMethod\CommandConstantReturnCodeRector;
use Rector\Transform\Rector\Attribute\AttributeKeyToClassConstFetchRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/config',
    ])
    // ->withImportNames()
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/SolidInvoice_KernelDevDebugContainer.xml')
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withSets([
        // General
        SetList::CODE_QUALITY,

        // PHP
        LevelSetList::UP_TO_PHP_81,

        // PHPUnit
        PHPUnitSetList::PHPUNIT_70,
        PHPUnitSetList::PHPUNIT_80,
        PHPUnitSetList::PHPUNIT_90,
        // PHPUnitSetList::PHPUNIT_91,
        PHPUnitSetList::PHPUNIT_100,
        // PHPUnitSetList::PHPUNIT_EXCEPTION,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,

        // Doctrine
        DoctrineSetList::DOCTRINE_COLLECTION_22,
        DoctrineSetList::DOCTRINE_COMMON_20,
        DoctrineSetList::DOCTRINE_DBAL_30,
        DoctrineSetList::DOCTRINE_DBAL_40,
        // DoctrineSetList::DOCTRINE_ORM_29,
        DoctrineSetList::DOCTRINE_ORM_213,
        DoctrineSetList::GEDMO_ANNOTATIONS_TO_ATTRIBUTES,
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
    ])
    ->withSkip([
        // Skip for new can be added/adjusted later
        PreferPHPUnitThisCallRector::class, // Use PreferPHPUnitSelfCallRector instead
        ControllerMethodInjectionToConstructorRector::class,
        AssertEmptyNullableObjectToAssertInstanceofRector::class,
        AssertEqualsToSameRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ContainerGetNameToTypeInTestsRector::class,
        CoversAnnotationWithValueToAttributeRector::class,
        AddInstanceofAssertForNullableInstanceRector::class,
        AssertEqualsToSameRector::class,
        WithCallbackIdenticalToStandaloneAssertsRector::class,
        CreateStubOverCreateMockArgRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        ExplicitBoolCompareRector::class,
        ConvertStaticToSelfRector::class,
        NarrowUnusedSetUpDefinedPropertyRector::class,
        ExpressionCreateMockToCreateStubRector::class,
        ClosureToArrowFunctionRector::class,
        DeclareStrictTypesTestsRector::class,
        AnnotationWithValueToAttributeRector::class,
        AssertFuncCallToPHPUnitAssertRector::class,
        LiteralGetToRequestClassConstantRector::class,
        BareCreateMockAssignToDirectUseRector::class,
        ReadOnlyPropertyRector::class,
        RemoveUnusedVariableInCatchRector::class,
        FinalizeTestCaseClassRector::class,
        StringCastAssertStringContainsStringRector::class,
        SafeDeclareStrictTypesRector::class,
        ResponseStatusCodeRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        SortCallLikeNamedArgsRector::class,
        RemoveUnusedRequestParamRector::class,
        DisallowedEmptyRuleFixerRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        AddInstanceofAssertForNullableArgumentRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        AttributeKeyToClassConstFetchRector::class,
        InlineConstructorDefaultToPropertyRector::class,
        SortAttributeNamedArgsRector::class,
        FunctionFirstClassCallableRector::class,
        WebTestCaseAssertResponseCodeRector::class,
        CombineIfRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
        CriteriaOrderingConstantsDeprecationRector::class,
        SimplifyIfNullableReturnRector::class,
        StringableForToStringRector::class,
        CleanupUnneededNullsafeOperatorRector::class,
        UnnecessaryTernaryExpressionRector::class,
        CommandConstantReturnCodeRector::class,
        YieldDataProviderRector::class,
        SimplifyBoolIdenticalTrueRector::class,
        SimplifyIfReturnBoolRector::class,
        ThrowWithPreviousExceptionRector::class,
        StringClassNameToClassConstantRector::class,
        InlineIfToExplicitIfRector::class,
        ActionSuffixRemoverRector::class,
        PropertyCreateMockToCreateStubRector::class,
        RepeatedOrEqualToInArrayRector::class,
        DecorateWillReturnMapWithExpectsMockRector::class,
        SingleInArrayToCompareRector::class,
        RenameClassRector::class,
        AssertComparisonToSpecificMethodRector::class,
        SimplifyDeMorganBinaryRector::class,
        SimplifyRegexPatternRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        AssertSameBoolNullToSpecificMethodRector::class,
        SimplifyIfElseToTernaryRector::class,
        IfIssetToCoalescingRector::class,
        ParameterBagTypedGetMethodCallRector::class,
        ClassOnObjectRector::class,
    ]);
