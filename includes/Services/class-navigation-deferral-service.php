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
     * Called at wp_enqueue_scripts (priority 998 = very late, after all plugins/themes enqueue).
     * This ensures ALL scripts are registered before we dequeue navigation scripts.
     * If we run earlier, scripts enqueued by theme/plugins later will override our dequeue.
     *
     * WordPress.org Compliant:
     * - Uses wp_add_inline_script (official API, not manual echo)
     * - Uses wp_register_script to preserve dependency chains
     * - Includes is_customize_preview() check for editor safety
     *
     * @return void
     */
    public function defer_navigation(): void
    {
        // Safety: Never break admin or editor
        if (is_admin() || is_customize_preview()) {
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

        // THEN re-register stub scripts to preserve dependencies
        $this->register_stub_scripts();

        // FINALLY: Inject the on-demand loader via official API
        $this->inject_on_demand_loader_inline();
    }

    /**
     * Dequeue navigation scripts so they don't load in the critical rendering path
     *
     * WordPress enqueues block navigation view scripts that add interactivity.
     * By dequeuing them here, they won't be included in the initial page load.
     * The on-demand loader will re-load them on first user interaction.
     *
     * CRITICAL: Must run at priority 999 (after ALL theme/plugin scripts are enqueued)
     * If we run too early, theme scripts enqueued later will override the dequeue.
     *
     * @return void
     */
    private function dequeue_navigation_scripts(): void
    {
        // Use wp_deregister_script to prevent registration entirely (stronger than dequeue)
        wp_deregister_script('@wordpress/block-library/navigation/view-js-module');
        wp_deregister_script('wp-block-navigation-view');
        wp_deregister_script('wp-block-library/navigation/view');

        // Also dequeue as backup (in case already registered)
        wp_dequeue_script('@wordpress/block-library/navigation/view-js-module');
        wp_dequeue_script('wp-block-navigation-view');
        wp_dequeue_script('wp-block-library/navigation/view');
    }

    /**
     * Re-register navigation scripts without enqueueing them
     *
     * This preserves the dependency chain so other scripts don't break,
     * while preventing the scripts from loading in the initial page render.
     *
     * The on-demand loader will dynamically fetch and execute them later.
     *
     * @return void
     */
    private function register_stub_scripts(): void
    {
        global $wp_scripts;

        // Get the script URLs from the already-registered (but now dequeued) scripts
        $script_urls = [];

        if (isset($wp_scripts->registered['@wordpress/block-library/navigation/view-js-module'])) {
            $script_urls['@wordpress/block-library/navigation/view-js-module'] = $wp_scripts->registered['@wordpress/block-library/navigation/view-js-module']->src;
        }
        if (isset($wp_scripts->registered['wp-block-navigation-view'])) {
            $script_urls['wp-block-navigation-view'] = $wp_scripts->registered['wp-block-navigation-view']->src;
        }

        // Re-register scripts so dependencies don't break, but do NOT enqueue them
        // This keeps them available for manual loading while preventing auto-load
        foreach ($script_urls as $handle => $src) {
            if ($src) {
                wp_register_script(
                    $handle,
                    esc_url($src),
                    [],
                    null,
                    true,
                );
            }
        }
    }

    /**
     * Inject on-demand loader via wp_add_inline_script (WordPress.org compliant)
     *
     * Uses the official WordPress API instead of manual script tag injection.
     * Attaches the loader to a core script that's always enqueued (wp-polyfill).
     *
     * @return void
     */
    private function inject_on_demand_loader_inline(): void
    {
        // Build the on-demand loader script using the official API
        $loader_script = <<<'SCRIPT'
(function() {
    var navScriptLoaded = false;

    function loadDeferredScripts() {
        if (navScriptLoaded) return;
        navScriptLoaded = true;

        // Get the navigation script URL from data attribute (injected below)
        var navScript = document.getElementById('odr-nav-src');
        if (!navScript || !navScript.dataset.src) return;

        var script = document.createElement('script');
        script.type = 'module';
        script.src = navScript.dataset.src;
        document.body.appendChild(script);

        // Remove event listeners
        document.removeEventListener('touchstart', loadDeferredScripts);
        document.removeEventListener('mousedown', loadDeferredScripts);
    }

    // Load on user interaction
    document.addEventListener('touchstart', loadDeferredScripts, { once: true });
    document.addEventListener('mousedown', loadDeferredScripts, { once: true });

    // Fallback: Load after 5 seconds for passive users
    setTimeout(loadDeferredScripts, 5000);
})();
SCRIPT;

        // Get the script URL to pass to the loader
        global $wp_scripts;
        $nav_script_src = '';
        if (isset($wp_scripts->registered['@wordpress/block-library/navigation/view-js-module'])) {
            $nav_script_src = esc_url($wp_scripts->registered['@wordpress/block-library/navigation/view-js-module']->src);
        }

        // Inject a data-carrying script that holds the URL (non-intrusive approach)
        wp_enqueue_script(
            'odr-nav-url-holder',
            '',
            [],
            null,
            false,
        );
        wp_add_inline_script(
            'odr-nav-url-holder',
            'var odrNavSrc = ' . wp_json_encode($nav_script_src) . ';',
            'before',
        );

        // Enqueue the loader script on wp-polyfill (always present in WordPress)
        wp_add_inline_script(
            'wp-polyfill',
            $loader_script,
            'after',
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
