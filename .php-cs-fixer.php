<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src');

$header = <<<'EOF'
This file is part of SolidInvoice project.

(c) Pierre du Plessis <open-source@solidworx.co>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$config = new PhpCsFixer\Config();

return $config
    ->setRules(
        [
            '@PSR1' => true,
            '@PSR2' => true,
            '@PSR12' => true,
            '@Symfony' => true,
            // '@Symfony:risky' => true,
            // '@PSR12:risky' => true,
            // '@PhpCsFixer:risky' => true,
            // '@PhpCsFixer' => true,
            'concat_space' => ['spacing' => 'one'],
            'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
            'ordered_imports' => ['imports_order' => ['const', 'class', 'function']],
            'array_syntax' => ['syntax' => 'short'],
            'phpdoc_no_package' => true,
            'phpdoc_summary' => false,
            'declare_strict_types' => true,
            'strict_param' => true,
            'not_operator_with_successor_space' => true,
            'header_comment' => [
                'comment_type' => 'comment',
                'header' => \trim($header),
                'location' => 'after_declare_strict',
                'separate' => 'both',
            ],
        ]
    )
    ->setFinder($finder)
    ->setRiskyAllowed(true);
