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

use PhpCsFixer\Fixer\Casing\MagicConstantCasingFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\ExplicitIndirectVariableFixer;
use PhpCsFixer\Fixer\LanguageConstruct\FunctionToConstantFixer;
use PhpCsFixer\Fixer\Operator\NewWithBracesFixer;
use PhpCsFixer\Fixer\Operator\StandardizeIncrementFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveDeadVarThisFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

$header = <<<'EOF'
This file is part of SolidInvoice project.

(c) Pierre du Plessis <open-source@solidworx.co>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/migrations',
        __DIR__ . '/rector.php',
        __FILE__,
    ])
    ->withSets([
        SetList::PSR_12,
        SetList::SPACES,
        SetList::DOCBLOCK,
        SetList::COMMENTS,
        // SetList::PHPUNIT,
        SetList::NAMESPACES,
        SetList::CLEAN_CODE,
    ])
    ->withRules([
        PhpUnitMethodCasingFixer::class,
        FunctionToConstantFixer::class,
        ExplicitStringVariableFixer::class,
        ExplicitIndirectVariableFixer::class,
        NewWithBracesFixer::class,
        StandardizeIncrementFixer::class,
        SelfAccessorFixer::class,
        MagicConstantCasingFixer::class,
        NoUselessElseFixer::class,
        SingleQuoteFixer::class,
        VoidReturnFixer::class,
    ])
    ->withConfiguredRule(SingleClassElementPerStatementFixer::class, ['elements' => ['const', 'property']])
    ->withConfiguredRule(ClassDefinitionFixer::class, ['single_line' => true])
    ->withConfiguredRule(OrderedImportsFixer::class, ['imports_order' => ['const', 'class', 'function']])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'comment_type' => 'comment',
        'header' => trim($header),
        'location' => 'after_declare_strict',
        'separate' => 'both',
    ])
    ->withSkip([
        MethodChainingIndentationFixer::class => [
            __DIR__ . '/src/PaymentBundle/DependencyInjection/Configuration.php',
            __DIR__ . '/src/DataGridBundle/DependencyInjection/GridConfiguration.php',
        ],
        HeaderCommentFixer::class => [
            __DIR__ . '/config/reference.php',
        ],
        __DIR__ . '/config/env',
        PhpdocLineSpanFixer::class,
        RemoveDeadVarThisFixer::class,
    ]);
