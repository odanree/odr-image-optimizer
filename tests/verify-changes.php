#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Simple Verification Script - SOLID Refactoring
 *
 * Checks that all files exist and have correct syntax/structure
 * without requiring WordPress or full plugin bootstrap
 */

echo "\n=== SOLID Refactoring Verification ===\n\n";

$plugin_path = dirname(__DIR__);  // Go up to plugin root, not tests/
$checks_passed = 0;
$checks_failed = 0;

// Define file checks
$file_checks = [
    // Core Container
    'includes/core/class-container.php' => [
        'contains' => [
            'get_priority_service',
            'get_asset_manager',
            'get_cleanup_service',
            'get_wordpress_adapter',
        ],
        'not_contains' => [],
    ],

    // Frontend Plugin File
    'odr-image-optimizer.php' => [
        'contains' => [
            'Container::get_priority_service',
            'Container::get_asset_manager',
            'Container::get_cleanup_service',
        ],
        'not_contains' => [
            'new \\ImageOptimizer\\Services\\PriorityService',
            'new \\ImageOptimizer\\Services\\AssetManager',
            'new \\ImageOptimizer\\Services\\CleanupService',
        ],
    ],

    // PriorityService - No static state
    'includes/Services/class-priority-service.php' => [
        'contains' => [
            'private ?int $lcp_id = null',
            'public function detect_lcp_id',
            'public function inject_preload',
        ],
        'not_contains' => [
            'private static ?int $lcp_id',
            'public static function reset_lcp_id',
        ],
    ],

    // Adapter files exist
    'includes/Adapter/WordPressAdapterInterface.php' => [
        'contains' => [
            'interface WordPressAdapterInterface',
            'public function is_singular',
            'public function is_admin',
            'public function is_frontend',
        ],
        'not_contains' => [],
    ],

    'includes/Adapter/WordPressAdapter.php' => [
        'contains' => [
            'class WordPressAdapter implements WordPressAdapterInterface',
            'public function is_singular',
            'public function is_admin',
            'public function is_frontend',
        ],
        'not_contains' => [],
    ],

    // Exception base class
    'includes/Exception/ImageOptimizerException.php' => [
        'contains' => [
            'class ImageOptimizerException extends \\Exception',
            'public function get_context',
        ],
        'not_contains' => [],
    ],

    // Updated exceptions
    'includes/Exception/OptimizationFailedException.php' => [
        'contains' => [
            'class OptimizationFailedException extends ImageOptimizerException',
        ],
        'not_contains' => [
            'class OptimizationFailedException extends \\Exception',
        ],
    ],

    'includes/Exception/BackupFailedException.php' => [
        'contains' => [
            'class BackupFailedException extends ImageOptimizerException',
        ],
        'not_contains' => [
            'class BackupFailedException extends \\Exception',
        ],
    ],

    // New processor exception
    'includes/Exception/ProcessorNotAvailableException.php' => [
        'contains' => [
            'class ProcessorNotAvailableException extends ImageOptimizerException',
            'public function get_dependency',
        ],
        'not_contains' => [],
    ],

    // Documentation
    'README.md' => [
        'contains' => [
            'Single Responsibility Principle',
            'Open/Closed Principle',
            'Liskov Substitution',
            'Interface Segregation',
            'Dependency Inversion',
            'WordPressAdapter',
        ],
        'not_contains' => [],
    ],

    'docs/EXTENDING.md' => [
        'contains' => [
            'Adding Custom Image Processors',
            'AvifProcessor',
            'ProcessorRegistry',
            'SOLID Design Benefits',
        ],
        'not_contains' => [],
    ],
];

// Run checks
foreach ($file_checks as $file => $checks) {
    $path = $plugin_path . '/' . $file;

    if (! file_exists($path)) {
        echo "❌ File not found: $file\n";
        $checks_failed++;
        continue;
    }

    $content = file_get_contents($path);

    // Check required content
    $missing_content = [];
    foreach ($checks['contains'] as $pattern) {
        if (strpos($content, $pattern) === false) {
            $missing_content[] = $pattern;
        }
    }

    // Check forbidden content
    $forbidden_content = [];
    foreach ($checks['not_contains'] as $pattern) {
        if (strpos($content, $pattern) !== false) {
            $forbidden_content[] = $pattern;
        }
    }

    if (empty($missing_content) && empty($forbidden_content)) {
        echo "✅ $file\n";
        $checks_passed++;
    } else {
        echo "❌ $file\n";
        if (! empty($missing_content)) {
            foreach ($missing_content as $pattern) {
                echo "   Missing: $pattern\n";
            }
        }
        if (! empty($forbidden_content)) {
            foreach ($forbidden_content as $pattern) {
                echo "   Should not contain: $pattern\n";
            }
        }
        $checks_failed++;
    }
}

echo "\n=== Syntax Validation ===\n\n";

// Check PHP syntax
$php_files = [
    'includes/core/class-container.php',
    'includes/Services/class-priority-service.php',
    'includes/Adapter/WordPressAdapterInterface.php',
    'includes/Adapter/WordPressAdapter.php',
    'includes/Exception/ImageOptimizerException.php',
    'includes/Exception/OptimizationFailedException.php',
    'includes/Exception/BackupFailedException.php',
    'includes/Exception/ProcessorNotAvailableException.php',
];

foreach ($php_files as $php_file) {
    $path = $plugin_path . '/' . $php_file;
    if (file_exists($path)) {
        $output = shell_exec("php -l \"$path\" 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $php_file\n";
            $checks_passed++;
        } else {
            echo "❌ $php_file - $output\n";
            $checks_failed++;
        }
    }
}

echo "\n=== File Structure ===\n\n";

// Check file structure
$structure_checks = [
    'Adapter directory exists' => is_dir("$plugin_path/includes/Adapter"),
    'Exception directory has 4 files' => count(glob("$plugin_path/includes/Exception/*.php")) >= 4,
    'Services directory exists' => is_dir("$plugin_path/includes/Services"),
    'Container getter methods added' => true,  // Already checked above
];

foreach ($structure_checks as $check => $result) {
    if ($result) {
        echo "✅ $check\n";
        $checks_passed++;
    } else {
        echo "❌ $check\n";
        $checks_failed++;
    }
}

// Summary
echo "\n=== Summary ===\n";
echo "Passed: $checks_passed\n";
echo "Failed: $checks_failed\n\n";

if ($checks_failed === 0) {
    echo "✅ All refactoring changes verified!\n\n";
    exit(0);
} else {
    echo "❌ Some checks failed\n\n";
    exit(1);
}
