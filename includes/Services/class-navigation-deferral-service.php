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

        // CRITICAL: Dequeue navigation scripts FIRST so they don't load in head
        $this->dequeue_navigation_scripts();

        // THEN inject the on-demand loader that will load them later
        $this->inject_on_demand_loader();
    }

    /**
     * Dequeue navigation scripts so they don't load in the critical rendering path
     *
     * WordPress enqueues block navigation view scripts that add interactivity.
     * By dequeuing them here, they won't be included in the initial page load.
     * The on-demand loader will re-load them on first user interaction.
     *
     * @return void
     */
    private function dequeue_navigation_scripts(): void
    {
        // Dequeue the block library navigation view script (WordPress 6.3+)
        wp_dequeue_script('@wordpress/block-library/navigation/view-js-module');
        wp_dequeue_script('wp-block-navigation-view');

        // Also dequeue the legacy view script in case it's used
        wp_dequeue_script('wp-block-library/navigation/view');
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
        // Get the WordPress block navigation script URL
        global $wp_scripts;
        $nav_script_url = '';

        // Try to get the script URL from registered scripts
        if (isset($wp_scripts->registered['@wordpress/block-library/navigation/view-js-module'])) {
            $nav_script_url = $wp_scripts->registered['@wordpress/block-library/navigation/view-js-module']->src;
        } elseif (isset($wp_scripts->registered['wp-block-navigation-view'])) {
            $nav_script_url = $wp_scripts->registered['wp-block-navigation-view']->src;
        }

        // Build full URL if it's a relative path
        if ($nav_script_url && 0 !== strpos($nav_script_url, 'http')) {
            $nav_script_url = home_url($nav_script_url);
        }

        $script = <<<'SCRIPT'
(function() {
    var navScriptUrl = %s;
    var navScriptLoaded = false;
    
    function loadDeferredScripts() {
        if (navScriptLoaded || !navScriptUrl) return;
        navScriptLoaded = true;
        
        // Create script element
        var script = document.createElement('script');
        script.type = 'module';
        script.src = navScriptUrl;
        document.body.appendChild(script);
        
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

        // Inject the script URL into the loader
        $inline_script = sprintf($script, wp_json_encode($nav_script_url));

        // Output inline script in the head
        wp_enqueue_script(
            'odr-navigation-deferral-loader',
            '',
            [],
            null,
            false,
        );

        // Use inline script instead of URL
        add_filter(
            'script_loader_tag',
            function ($tag, $handle) use ($inline_script) {
                if ('odr-navigation-deferral-loader' === $handle) {
                    return '<script id="' . esc_attr($handle) . '">' . $inline_script . '</script>';
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
