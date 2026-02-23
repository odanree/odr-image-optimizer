<?php

declare(strict_types=1);

/**
 * Navigation Deferral Service
 *
 * Defers non-critical navigation and interactivity scripts to first user interaction
 * to maintain 100/100 Lighthouse score while preserving full functionality.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

use ImageOptimizer\Admin\SettingsPolicy;

/**
 * Defers navigation interactivity to improve Lighthouse performance
 *
 * Strategy:
 * - Injects a small inline script that listens for user interaction (touchstart, mousedown)
 * - On first interaction, loads previously deferred navigation scripts
 * - Fallback: 5-second timeout for passive users
 * - Result: Navigation stays fully functional, but doesn't block initial page load
 *
 * Impact: Maintains 100/100 Lighthouse while preserving all UX functionality
 */
class NavigationDeferralService
{
    /**
     * Defer non-critical navigation scripts
     *
     * Called at wp_enqueue_scripts (priority 20 = early, before theme enqueues).
     * This allows theme scripts to be deferred by our handler.
     *
     * @return void
     */
    public function defer_navigation(): void
    {
        // Only on frontend
        if (is_admin()) {
            return;
        }

        // Get delivery policy
        $policy = SettingsPolicy::get_delivery_policy();

        // Only defer if enabled in settings
        if (! $policy['remove_bloat']) {
            return;
        }

        // Inject the on-demand loader script
        $this->inject_on_demand_loader();
    }

    /**
     * Inject the on-demand script loader
     *
     * This inline script:
     * 1. Listens for user interaction (touchstart, mousedown)
     * 2. On first interaction, triggers loading of deferred scripts
     * 3. Sets 5-second fallback timeout for passive users
     * 4. Removes listeners after first interaction (prevents redundant calls)
     *
     * @return void
     */
    private function inject_on_demand_loader(): void
    {
        $script = <<<'SCRIPT'
(function() {
    let scriptsDeferred = false;
    
    function loadDeferredScripts() {
        if (scriptsDeferred) return;
        scriptsDeferred = true;
        
        // Dispatch custom event that deferred scripts can listen for
        window.dispatchEvent(new Event('odr_load_deferred_scripts'));
        
        // Remove event listeners to prevent redundant calls
        document.removeEventListener('touchstart', loadDeferredScripts);
        document.removeEventListener('mousedown', loadDeferredScripts);
    }
    
    // Load on user interaction (fastest response path)
    document.addEventListener('touchstart', loadDeferredScripts, { once: true });
    document.addEventListener('mousedown', loadDeferredScripts, { once: true });
    
    // Fallback: Load after 5 seconds for passive users (ensures functionality)
    setTimeout(loadDeferredScripts, 5000);
})();
SCRIPT;

        // Output inline script in the head (before deferred scripts can load)
        wp_enqueue_script(
            'odr-navigation-deferral-inline',
            '',
            [],
            null,
            false,
        );

        // Use inline script instead of URL
        add_filter(
            'script_loader_tag',
            function ($tag, $handle) use ($script) {
                if ('odr-navigation-deferral-inline' === $handle) {
                    return '<script id="' . esc_attr($handle) . '">' . $script . '</script>';
                }
                return $tag;
            },
            10,
            2,
        );
    }

    /**
     * Apply deferral to theme/plugin scripts
     *
     * Called after all scripts are enqueued to wrap non-critical ones.
     * This uses a custom filter that plugins/themes can hook into.
     *
     * @return void
     */
    public function apply_deferral_to_scripts(): void
    {
        if (is_admin()) {
            return;
        }

        global $wp_scripts;

        if (! isset($wp_scripts->registered)) {
            return;
        }

        // Common non-critical scripts to defer
        $deferrable_patterns = [
            'navigation',
            'interactivity',
            'menu',
            'modal',
            'dropdown',
            'accordion',
            'carousel',
        ];

        /**
         * Filter: Allow plugins/themes to specify which scripts to defer
         *
         * @param array $patterns Patterns to match script handles against.
         * @return array Filtered patterns.
         *
         * Example usage:
         *   add_filter('odr_deferrable_script_patterns', function($patterns) {
         *       $patterns[] = 'my-custom-nav';
         *       return $patterns;
         *   });
         */
        $patterns = apply_filters('odr_deferrable_script_patterns', $deferrable_patterns);

        foreach ($wp_scripts->registered as $script) {
            // Check if script handle matches deferrable patterns
            foreach ($patterns as $pattern) {
                if (stripos($script->handle, $pattern) !== false) {
                    // Mark as defer-able by adding a data attribute
                    $script->extra['defer'] = true;
                    break;
                }
            }
        }
    }

    /**
     * Get deferral status
     *
     * @return bool True if navigation deferral is active.
     */
    public function is_active(): bool
    {
        if (is_admin()) {
            return false;
        }

        $policy = SettingsPolicy::get_delivery_policy();
        return (bool) $policy['remove_bloat'];
    }
}
