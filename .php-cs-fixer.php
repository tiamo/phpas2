<?php

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->in(__DIR__.DIRECTORY_SEPARATOR.'tests')
    ->in(__DIR__.DIRECTORY_SEPARATOR.'src');

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['expectedDeprecation']],
        '@Symfony' => true,
        'phpdoc_no_empty_return' => false,
        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => false,
        'binary_operator_spaces' => [
            'operators' => [
                '=>' => 'align',
                '=' => 'align',
            ],
        ],
        'concat_space' => ['spacing' => 'one'],
        'not_operator_with_space' => false,
    ])
    ->setFinder($finder);

return $config;
