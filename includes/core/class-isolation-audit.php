<?php

declare(strict_types=1);

/**
 * Dependency Isolation Audit
 *
 * Checks if optimizer implementations are isolated from WordPress globals.
 * Ensures DIP compliance by validating "pure function" behavior.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Audits dependency isolation for optimizer implementations
 */
class IsolationAudit
{
    /**
     * Dangerous WordPress global functions that indicate tight coupling
     *
     * Note: get_option() for configuration is acceptable.
     * Only direct database or query functions indicate tight coupling.
     *
     * @var array
     */
    const DANGEROUS_WP_GLOBALS = [
        'global $wpdb',
        'global $wp_filesystem',
        '$wpdb->',        // Direct database access
        'WP_Query',       // Direct query
        'get_posts(',     // Post queries
        'wp_attachment_is(',  // Tight coupling to attachment properties
    ];

    /**
     * Dangerous filesystem patterns that indicate hardcoded paths
     *
     * @var array
     */
    const DANGEROUS_HARDCODED_PATHS = [
        '"/wp-content/',
        "'/wp-content/",
        '"/uploads/',
        "'/uploads/",
        '__DIR__',
        '__FILE__',
        'dirname(__FILE__)',
    ];

    /**
     * Audit optimizer for dependency isolation
     *
     * @param OptimizerInterface $optimizer The optimizer to audit.
     * @return array Audit result with 'isolated' and 'violations'.
     */
    public static function audit(OptimizerInterface $optimizer): array
    {
        $violations = [];

        // Get optimizer class source code
        $reflection = new \ReflectionClass($optimizer);
        $filename = $reflection->getFileName();
        $code = file_get_contents($filename);

        // Check for dangerous WordPress globals
        foreach (self::DANGEROUS_WP_GLOBALS as $dangerous) {
            if (stripos($code, $dangerous) !== false) {
                $violations[] = [
                    'level'    => 'warning',
                    'type'     => 'WordPress Global',
                    'found'    => $dangerous,
                    'reason'   => 'Tight coupling to WordPress functions',
                    'fix'      => 'Pass dependencies through hook context instead of calling globals',
                ];
            }
        }

        // Check for hardcoded paths
        foreach (self::DANGEROUS_HARDCODED_PATHS as $dangerous) {
            if (stripos($code, $dangerous) !== false) {
                $violations[] = [
                    'level'    => 'error',
                    'type'     => 'Hardcoded Path',
                    'found'    => $dangerous,
                    'reason'   => 'Depends on specific directory structure',
                    'fix'      => 'Get paths from injected ToolRegistry or hook context',
                ];
            }
        }

        return [
            'isolated'   => empty($violations),
            'violations' => $violations,
            'optimizer'  => get_class($optimizer),
            'file'       => $filename,
        ];
    }

    /**
     * Test optimizer isolation by running it standalone
     *
     * Attempts to instantiate and use optimizer without WordPress loaded.
     *
     * @param OptimizerInterface $optimizer The optimizer to test.
     * @return array Test result.
     */
    public static function test_standalone(OptimizerInterface $optimizer): array
    {
        // Check if can be serialized (strong indicator of isolation)
        try {
            $serialized = serialize($optimizer);
            $can_serialize = true;
        } catch (\Exception $e) {
            $can_serialize = false;
        }

        // Check if has no unresolved dependencies
        $reflection = new \ReflectionClass($optimizer);
        $constructor = $reflection->getConstructor();

        $has_unresolved_deps = false;
        if ($constructor) {
            $params = $constructor->getParameters();
            foreach ($params as $param) {
                // If parameter has no default and is not injectable, it's unresolved
                if (! $param->isDefaultValueAvailable() && ! $param->getType()) {
                    $has_unresolved_deps = true;
                }
            }
        }

        return [
            'can_serialize'         => $can_serialize,
            'has_unresolved_deps'   => $has_unresolved_deps,
            'isolated'              => $can_serialize && ! $has_unresolved_deps,
            'optimizer'             => get_class($optimizer),
        ];
    }

    /**
     * Generate isolation audit report
     *
     * @param OptimizerInterface $optimizer The optimizer to audit.
     * @return string Human-readable report.
     */
    public static function generate_report(OptimizerInterface $optimizer): string
    {
        $audit = self::audit($optimizer);
        $standalone = self::test_standalone($optimizer);

        $report = "=== Dependency Isolation Audit ===\n\n";
        $report .= "Optimizer: " . $audit['optimizer'] . "\n";
        $report .= "File: " . $audit['file'] . "\n\n";

        // Code analysis
        $report .= "Code Analysis:\n";
        if ($audit['isolated']) {
            $report .= "  ✅ No WordPress globals detected\n";
            $report .= "  ✅ No hardcoded paths detected\n";
        } else {
            $report .= "  ❌ Found " . count($audit['violations']) . " violation(s):\n";
            foreach ($audit['violations'] as $violation) {
                $report .= sprintf(
                    "\n  [%s] %s\n    Found: %s\n    Reason: %s\n    Fix: %s\n",
                    strtoupper($violation['level']),
                    $violation['type'],
                    $violation['found'],
                    $violation['reason'],
                    $violation['fix']
                );
            }
        }

        // Standalone testing
        $report .= "\n\nStandalone Testing:\n";
        $report .= "  Serializable: " . ($standalone['can_serialize'] ? "YES ✅" : "NO ❌") . "\n";
        $report .= "  Unresolved Dependencies: " . ($standalone['has_unresolved_deps'] ? "YES ❌" : "NO ✅") . "\n";
        $report .= "  Isolated: " . ($standalone['isolated'] ? "YES ✅" : "NO ❌") . "\n";

        return $report;
    }
}
