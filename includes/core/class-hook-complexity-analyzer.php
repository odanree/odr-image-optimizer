<?php

declare(strict_types=1);

/**
 * Hook Complexity Analyzer
 *
 * Detects "filter sprawl" where too many hooks make the system hard to extend.
 * Validates Open/Closed Principle compliance.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Analyzes hook structure for complexity
 */
class HookComplexityAnalyzer
{
    /**
     * Ideal hooks structure for image optimization
     *
     * Should have:
     * - 1 Primary Filter: Choose optimizer engine
     * - 1 Primary Action: Post-processing (WebP, etc)
     * - Optional: Before/after hooks for context data
     *
     * @var array
     */
    const IDEAL_HOOK_STRUCTURE = [
        'filters'  => [
            'image_optimizer_engine'      => 'Choose which optimizer to use',
            'image_optimizer_result'      => 'Modify optimization result',
        ],
        'actions'  => [
            'image_optimizer_post_process' => 'WebP, thumbnails, etc',
        ],
        'data_hooks' => [
            'image_optimizer_before_optimize' => 'Pass ImageContext before optimization',
            'image_optimizer_after_optimize'  => 'Pass ImageContext after optimization',
        ],
    ];

    /**
     * Analyze current hook usage
     *
     * @return array Analysis results with 'hook_count', 'complexity_score', 'issues'.
     */
    public static function analyze(): array
    {
        global $wp_filter;

        $optimizer_hooks = self::get_optimizer_hooks($wp_filter);

        return [
            'total_hooks'      => count($optimizer_hooks),
            'filter_hooks'     => count(array_filter($optimizer_hooks, fn($h) => $h['type'] === 'filter')),
            'action_hooks'     => count(array_filter($optimizer_hooks, fn($h) => $h['type'] === 'action')),
            'complexity_score' => self::calculate_complexity_score($optimizer_hooks),
            'hooks'            => $optimizer_hooks,
            'issues'           => self::identify_issues($optimizer_hooks),
        ];
    }

    /**
     * Extract optimizer-related hooks from WordPress
     *
     * @param array $wp_filter The $GLOBALS['wp_filter'] array.
     * @return array Filtered hooks.
     */
    private static function get_optimizer_hooks(array $wp_filter): array
    {
        $optimizer_hooks = [];

        foreach ($wp_filter as $hook_name => $hook_data) {
            // Only look at image_optimizer hooks
            if (stripos($hook_name, 'image_optimizer') === false) {
                continue;
            }

            $callback_count = 0;
            if (is_array($hook_data)) {
                foreach ($hook_data as $priority => $callbacks) {
                    if (is_array($callbacks)) {
                        $callback_count += count($callbacks);
                    }
                }
            }

            $optimizer_hooks[$hook_name] = [
                'name'           => $hook_name,
                'type'           => 'filter', // Default; would need more logic to distinguish
                'callback_count' => $callback_count,
            ];
        }

        return $optimizer_hooks;
    }

    /**
     * Calculate complexity score
     *
     * Score calculation:
     * - 0 hooks: 0 (no extension possible)
     * - 1-3 hooks: 1 (ideal - simple, focused)
     * - 4-6 hooks: 2 (acceptable - multiple concerns)
     * - 7-10 hooks: 3 (warning - starting to fragment)
     * - 11+ hooks: 4 (critical - too much sprawl)
     *
     * @param array $hooks Array of hooks.
     * @return int Complexity score (0-4).
     */
    private static function calculate_complexity_score(array $hooks): int
    {
        $count = count($hooks);

        if ($count === 0) {
            return 0;
        } elseif ($count <= 3) {
            return 1;
        } elseif ($count <= 6) {
            return 2;
        } elseif ($count <= 10) {
            return 3;
        } else {
            return 4;
        }
    }

    /**
     * Identify potential issues with hook structure
     *
     * @param array $hooks Array of hooks.
     * @return array Issues found.
     */
    private static function identify_issues(array $hooks): array
    {
        $issues = [];

        $count = count($hooks);

        if ($count === 0) {
            $issues[] = [
                'level'    => 'error',
                'issue'    => 'No image_optimizer hooks registered',
                'reason'   => 'Plugin cannot be extended',
                'fix'      => 'Add image_optimizer filters to make system extensible',
            ];
        } elseif ($count > 10) {
            $issues[] = [
                'level'    => 'warning',
                'issue'    => "Too many hooks ($count). System is fragmented.",
                'reason'   => 'Developers must understand many extension points',
                'fix'      => 'Consolidate into 2-4 primary hooks (engine, result, post-process)',
            ];
        }

        // Check for data consistency across hooks
        $has_context_hooks = false;
        $has_array_hooks = false;

        foreach ($hooks as $hook) {
            if (strpos($hook['name'], 'image_optimizer_before') !== false ||
                strpos($hook['name'], 'image_optimizer_after') !== false) {
                $has_context_hooks = true;
            }
            if (strpos($hook['name'], 'optimize') !== false) {
                $has_array_hooks = true;
            }
        }

        if ($has_context_hooks && $has_array_hooks) {
            $issues[] = [
                'level'    => 'warning',
                'issue'    => 'Mixed hook data types (Context vs Array)',
                'reason'   => 'Developers must handle different data structures',
                'fix'      => 'Standardize all hooks to pass ImageContext objects',
            ];
        }

        return $issues;
    }

    /**
     * Get recommended hook structure
     *
     * @return array Recommended hooks.
     */
    public static function get_recommendations(): array
    {
        return [
            'primary_filter'     => [
                'name'        => 'image_optimizer_engine',
                'description' => 'Choose which optimizer to use',
                'usage'       => 'apply_filters("image_optimizer_engine", null, $context)',
                'returns'     => 'OptimizerInterface or null to use default',
            ],
            'primary_action'     => [
                'name'        => 'image_optimizer_post_process',
                'description' => 'Post-processing (WebP, thumbnails, etc)',
                'usage'       => 'do_action("image_optimizer_post_process", $result, $context)',
                'parameters'  => 'Result object and ImageContext',
            ],
            'data_hooks'         => [
                [
                    'name'        => 'image_optimizer_before_optimize',
                    'type'        => 'action',
                    'description' => 'Before optimization starts',
                    'context'     => 'ImageContext with original file info',
                ],
                [
                    'name'        => 'image_optimizer_after_optimize',
                    'type'        => 'action',
                    'description' => 'After optimization completes',
                    'context'     => 'ImageContext with optimization result',
                ],
            ],
        ];
    }

    /**
     * Generate complexity report
     *
     * @return string Human-readable report.
     */
    public static function generate_report(): string
    {
        $analysis = self::analyze();
        $recommendations = self::get_recommendations();

        $report = "=== Hook Complexity Analysis (OCP) ===\n\n";

        // Current state
        $report .= "Current State:\n";
        $report .= "  Total Hooks: " . $analysis['total_hooks'] . "\n";
        $report .= "  Filters: " . $analysis['filter_hooks'] . "\n";
        $report .= "  Actions: " . $analysis['action_hooks'] . "\n";
        $report .= "  Complexity Score: " . $analysis['complexity_score'] . "/4\n\n";

        // Scoring explanation
        $scores = [
            0 => '0 (No hooks - system not extensible)',
            1 => '1 (Ideal - simple, focused structure)',
            2 => '2 (Acceptable - multiple concerns)',
            3 => '3 (Warning - starting to fragment)',
            4 => '4 (Critical - too much sprawl)',
        ];
        $report .= "Score Meaning: " . ($scores[$analysis['complexity_score']] ?? 'Unknown') . "\n\n";

        // Issues
        if (! empty($analysis['issues'])) {
            $report .= "Issues Found:\n";
            foreach ($analysis['issues'] as $issue) {
                $report .= sprintf(
                    "\n❌ [%s] %s\n   Reason: %s\n   Fix: %s\n",
                    strtoupper($issue['level']),
                    $issue['issue'],
                    $issue['reason'],
                    $issue['fix']
                );
            }
        } else {
            $report .= "✅ No hook complexity issues detected\n\n";
        }

        // Recommendations
        $report .= "\n\nRecommended Hook Structure:\n";
        $report .= "Primary Filter:\n";
        $report .= "  - image_optimizer_engine: Choose optimizer implementation\n\n";
        $report .= "Primary Action:\n";
        $report .= "  - image_optimizer_post_process: WebP, caching, etc\n\n";
        $report .= "Data Hooks (pass ImageContext):\n";
        $report .= "  - image_optimizer_before_optimize\n";
        $report .= "  - image_optimizer_after_optimize\n";

        return $report;
    }
}
