<?php
$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/includes',
    ])
    ->exclude([
        'vendor',
        '.git',
        'node_modules',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'strict_types' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_trailing_whitespace' => true,
        'single_line_empty_body' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setFinder($finder);
