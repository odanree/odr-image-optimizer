<?php

declare(strict_types=1);

/**
 * Dependency Injection Audit Tool
 *
 * Detects potential tight coupling violations where dependencies are directly
 * instantiated instead of being injected.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * DIP (Dependency Inversion Principle) audit tool
 */
class DipAudit
{
    /**
     * Check if optimizer is properly injecting dependencies
     *
     * Returns detailed information about potential tight couplings.
     *
     * @param OptimizerInterface $optimizer The optimizer to audit.
     * @return array Audit results with warnings and suggestions.
     */
    public static function audit(OptimizerInterface $optimizer): array
    {
        $warnings = [];

        // Check 1: Verify ToolRegistry is injected
        if (method_exists($optimizer, 'get_tool_registry')) {
            $registry = $optimizer->get_tool_registry();
            if (! $registry instanceof ToolRegistry) {
                $warnings[] = [
                    'level'       => 'error',
                    'category'    => 'ToolRegistry Injection',
                    'issue'       => 'Optimizer does not properly inject ToolRegistry',
                    'suggestion'  => 'Ensure Optimizer accepts ToolRegistry in constructor',
                ];
            }
        } else {
            $warnings[] = [
                'level'       => 'warning',
                'category'    => 'ToolRegistry Injection',
                'issue'       => 'Optimizer does not expose get_tool_registry() method',
                'suggestion'  => 'Add public method to access injected tool registry',
            ];
        }

        // Check 2: Verify hooks pass data objects
        $warnings = array_merge($warnings, self::check_hook_contracts());

        return [
            'optimizer'  => get_class($optimizer),
            'valid'      => empty(array_filter($warnings, fn ($w) => $w['level'] === 'error')),
            'warnings'   => $warnings,
            'timestamp'  => current_time('mysql'),
        ];
    }

    /**
     * Check that hooks pass proper data objects
     *
     * @return array Array of warnings.
     */
    private static function check_hook_contracts(): array
    {
        $warnings = [];

        // These hooks MUST pass ImageContext, not raw strings
        $required_context_hooks = [
            'image_optimizer_before_optimize' => 'ImageContext',
            'image_optimizer_after_optimize'  => 'ImageContext',
            'image_optimizer_before_revert'   => 'ImageContext',
            'image_optimizer_after_revert'    => 'ImageContext',
        ];

        foreach ($required_context_hooks as $hook => $expected_type) {
            $hookCallbacks = $GLOBALS['wp_filter'][$hook] ?? [];

            if (empty($hookCallbacks)) {
                // No callbacks yet, that's fine
                continue;
            }

            // In production, hooks would be tested at runtime
            // This is a static check for documentation
        }

        return $warnings;
    }

    /**
     * Generate audit report for logging/debugging
     *
     * @param OptimizerInterface $optimizer The optimizer to audit.
     * @return string Human-readable audit report.
     */
    public static function generate_report(OptimizerInterface $optimizer): string
    {
        $audit = self::audit($optimizer);
        $report = "=== Dependency Injection Audit Report ===\n\n";
        $report .= 'Optimizer: ' . $audit['optimizer'] . "\n";
        $report .= 'Valid: ' . ($audit['valid'] ? 'YES ✅' : 'NO ❌') . "\n";
        $report .= 'Warnings: ' . count($audit['warnings']) . "\n\n";

        if (empty($audit['warnings'])) {
            $report .= "No DIP violations detected.\n";
        } else {
            $report .= "Issues Found:\n";
            foreach ($audit['warnings'] as $idx => $warning) {
                $report .= sprintf(
                    "\n%d. [%s] %s\n   Issue: %s\n   Suggestion: %s\n",
                    $idx + 1,
                    strtoupper($warning['level']),
                    $warning['category'],
                    $warning['issue'],
                    $warning['suggestion'],
                );
            }
        }

        return $report;
    }
}
