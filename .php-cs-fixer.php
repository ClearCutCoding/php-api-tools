<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->notPath('src/Symfony/Component/Translation/Tests/fixtures/resources.php')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'yoda_style' => false,
        'concat_space' => ['spacing' => 'one'],
        'class_attributes_separation' => [
            'elements' => ['method' => 'one', 'property' => 'one', 'trait_import' => 'one']
        ],
    ])
    ->setFinder($finder)
    ;
