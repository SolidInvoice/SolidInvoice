<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src');

return PhpCsFixer\Config::create()
    ->setRules(
        [
            '@PSR1' => true,
            '@PSR2' => true,
            '@Symfony' => true,
            'array_syntax' => array('syntax' => 'short'),
            'phpdoc_no_package' => true,
            'phpdoc_summary' => false,
            'declare_strict_types' => true,
            'strict_param' => true,
        ]
    )
    ->setFinder($finder)
;
