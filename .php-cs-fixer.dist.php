<?php

use PhpCsFixer\RuleSet\RuleSets;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'declare_strict_types' => true,
        'php_unit_strict' => false,
        'phpdoc_to_comment' => ['ignored_tags' => ['psalm-suppress']],
        'no_trailing_whitespace_in_string' => false,
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_indent' => false, // screws up inline docblock for member variables
        'blank_line_before_statement' => [
            'statements' => array_diff(RuleSets::getSetDefinition('@PhpCsFixer')->getRules()['blank_line_before_statement']['statements'], ['phpdoc']),
        ],
        'php_unit_test_class_requires_covers' => false,
        'single_line_comment_style' => false,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
