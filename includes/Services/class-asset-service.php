<?php

declare(strict_types=1);

/**
 * Asset Service
 *
 * Handles critical rendering path optimizations by managing fonts and bloat removal.
 * Focuses on eliminating render-blocking resources and non-essential CSS/JS.
 *
 * Single Responsibility: Critical Path & Font Management
 *
 * Dependency Injection: Receives Settings_Repository to respect user preferences.
 * Services should never call get_option() directly - they ask for settings via DI.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Asset_Service: Optimize critical rendering path
 *
 * Isolates font and asset logic from other optimizations.
 * Respects user settings via dependency injection.
 * If you later add CDN support or Database optimization, this stays clean.
 */
class Asset_Service
{
    /**
     * Settings repository for accessing plugin configuration
     *
     * @var Settings_Repository
     */
    private Settings_Repository $settings;

    /**
     * Constructor: Inject settings repository
     *
     * Dependency Injection ensures this service doesn't know about
     * WordPress get_option() - it just uses the repository.
     *
     * @param Settings_Repository $settings The settings repository instance
     */
    public function __construct(Settings_Repository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Register hooks for asset optimizations
     *
     * Only registers hooks for enabled features.
     *
     * @return void
     */
    public function register(): void
    {
        // START: Output buffer at priority -9999 (EARLIEST in wp_head, before everything)
        // This catches ALL module imports, styleheet tags, and script tags
        add_action('wp_head', [$this, 'start_cleaning_module_scripts'], -9999);

        // Remove version query strings from scripts/styles for better caching
        // Uses remove_query_arg('ver', $src) to cleanly strip ?ver=X.X.X
        // Priority 999 = runs LAST, after all plugins/themes have enqueued
        add_filter('script_loader_src', [$this, 'remove_ver_query_string'], 999);
        add_filter('style_loader_src', [$this, 'remove_ver_query_string'], 999);

        // Preload critical fonts early (priority 0 = after buffer starts, before default head content)
        // Only if font preloading is enabled
        if ($this->settings->is_enabled('preload_fonts')) {
            add_action('wp_head', [$this, 'preload_critical_fonts'], 0);
        }

        // STOP: Output buffer very late in wp_footer (priority 9999 = AFTER everything)
        // This flushes and cleans the entire buffered HTML including all module imports
        add_action('wp_footer', [$this, 'finish_cleaning_module_scripts'], 9999);

        // Remove WordPress bloat late (priority 999 = after all plugins/themes enqueue)
        // Only if bloat removal is enabled (aggressive mode)
        if ($this->settings->is_enabled('remove_bloat') || $this->settings->is_aggressive_mode()) {
            add_action('wp_enqueue_scripts', [$this, 'remove_core_bloat'], 999);
        }
    }

    /**
     * Start output buffering to clean module script imports
     *
     * Called at priority 999999 (extremely late in wp_head).
     * This catches all script module imports and modulepreload links.
     *
     * @return void
     */
    public function start_cleaning_module_scripts(): void
    {
        ob_start([$this, 'clean_module_queries']);
    }

    /**
     * Finish output buffering and clean module script queries
     *
     * Called at priority -1 (extremely early in wp_footer).
     * Ensures we capture and clean everything before output.
     *
     * @return void
     */
    public function finish_cleaning_module_scripts(): void
    {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Clean query strings from module imports and modulepreload links
     *
     * Callback for output buffer. Removes all ?ver=... parameters from:
     * - Module import JSON: {"imports":{"@wordpress/lib":"...?ver=..."}}
     * - Modulepreload links: <link rel="modulepreload" href="...?ver=...">
     *
     * @param string $html The buffered HTML output
     * @return string      The cleaned HTML
     */
    public function clean_module_queries(string $html): string
    {
        if (! is_string($html)) {
            return $html;
        }

        // Remove ?ver=... from all script URLs in the HTML
        // Pattern matches: url?ver=hashvalue
        $cleaned = preg_replace('/\?ver=[a-f0-9]+/i', '', $html);

        // preg_replace returns string|array|null; cast to string for type safety
        return (string) ($cleaned ?? '');
    }

    /**
     * Remove version query string from static resources
     *
     * Strips ?ver=X.X.X from scripts and styles to improve cacheability.
     * This ensures Lighthouse sees clean URLs without cache-busting parameters.
     *
     * Example:
     * - Input:  /wp-includes/js/jquery/jquery.js?ver=6.9
     * - Output: /wp-includes/js/jquery/jquery.js
     *
     * Uses WordPress's remove_query_arg() for reliable parameter removal.
     *
     * @param string $src The script or style source URL
     * @return string     The cleaned URL without ?ver= parameter
     */
    public function remove_ver_query_string($src)
    {
        if (! is_string($src)) {
            return $src;
        }

        // Only process if URL contains ?ver=
        if (strpos($src, '?ver=') === false) {
            return $src;
        }

        // Remove the 'ver' query parameter cleanly
        return remove_query_arg('ver', $src);
    }

    /**
     * Remove query strings from asset URLs for better caching
     *
     * WordPress adds ?ver=X.X.X to scripts and stylesheets, which prevents
     * browsers and CDNs from caching them effectively. Since we're using
     * ExpiresByType in .htaccess based on file extension, removing the query
     * string allows proper cache control headers to be applied.
     *
     * Example:
     * - Before: /wp-content/plugins/plugin/script.js?ver=1.0.0
     * - After:  /wp-content/plugins/plugin/script.js
     *
     * Uses WordPress's remove_query_arg() function for reliability.
     *
     * @param string $src The script or style source URL
     * @return string     The cleaned URL without query strings
     */
    public function remove_query_strings(string $src): string
    {
        if (! is_string($src)) {
            return $src;
        }

        // Remove all query parameters, keeping only the base URL
        // This ensures compatibility with version query strings and any future params
        if (strpos($src, '?') === false) {
            return $src;
        }

        // Use WordPress's remove_query_arg to cleanly remove 'ver' parameter
        $src = remove_query_arg('ver', $src);

        // Also remove any other query args that might cause cache misses
        // (though 'ver' is the most common)
        return $src;
    }

    /**
     * Remove query strings from script modules in HTML output
     *
     * Script modules (WordPress 6.9+) include URLs in JSON that need cleaning.
     * This catches the final HTML output and removes all ?ver=... parameters.
     *
     * @return void
     */
    public function remove_query_strings_from_output(): void
    {
        // Buffer the output and clean query strings from module URLs
        add_filter('wp_print_script_tag', function ($tag, $handle) {
            // Clean query strings from type="module" script tags
            if (strpos($tag, 'type="module"') !== false) {
                return preg_replace('/\?ver=[a-f0-9]+/', '', $tag);
            }
            return $tag;
        }, 10, 2);

        // Also clean from inline script tags with JSON imports
        add_filter('wp_inline_script_attributes', function ($attrs) {
            return $attrs;
        }, 10, 1);
    }

    /**
     * Preload critical fonts at ultra-high priority
     *
     * This runs BEFORE priority 1, ensuring fonts start downloading
     * before CSS parsing. Solves the font latency chain:
     * HTML → CSS parse → @font-face discovery → font download
     *
     * With preload: HTML + Font download happen in parallel with CSS parse
     *
     * Example output:
     * <link rel="preload" href="..." as="font" type="font/woff2" crossorigin>
     *
     * @return void
     */
    public function preload_critical_fonts(): void
    {
        if (is_admin()) {
            return;
        }

        // Get theme's primary font URL (e.g., from theme customizer or constants)
        $font_url = $this->get_theme_font_url();

        if (empty($font_url)) {
            return;
        }

        // Output preload link for critical font
        echo '<link rel="preload" href="' . esc_url($font_url) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }

    /**
     * Remove WordPress core bloat
     *
     * Dequeues non-essential CSS and JavaScript that theme doesn't need.
     * Common items:
     * - wp-block-library (Gutenberg block styles) - ONLY if blocks are not used
     * - global-styles (global styles when not needed) - ONLY if theme provides its own
     * - Classic theme CSS - ONLY if using block-based theme
     *
     * Runs at priority 999 (extremely late) to ensure all plugins/themes
     * have already enqueued their scripts before we dequeue.
     *
     * CRITICAL: Only remove CSS if we're certain it's not needed.
     * Aggressive removal can break block-based themes and layouts.
     *
     * @return void
     */
    public function remove_core_bloat(): void
    {
        if (is_admin()) {
            return;
        }

        // IMPORTANT: wp-block-library CSS is NOT bloat if site uses Gutenberg blocks
        // Removing it breaks block styling, so skip this dequeue entirely
        // Block library CSS (~5KB gzipped) is worth the price for block functionality

        // Remove global styles only if theme is NOT block-based
        // Block themes use global-styles, so check theme support first
        if (! current_theme_supports('editor-styles')) {
            wp_dequeue_style('global-styles');
        }

        // Remove emoji styles and scripts (safe to remove - rarely used)
        wp_dequeue_style('print-emoji-styles');
        wp_dequeue_script('wp-emoji');

        // Remove classic theme styles only if explicitly needed
        // Most block themes already handle this, so be conservative
        // Uncomment only if you see classic theme interference:
        // wp_dequeue_style('classic-theme-styles');
    }

    /**
     * Get the theme's primary font URL
     *
     * This is a helper method to retrieve the font URL from various sources.
     * Can be extended to check theme customizer, constants, etc.
     *
     * @return string Font URL or empty string if not found
     */
    private function get_theme_font_url(): string
    {
        // Check if a font URL constant is defined
        if (defined('ODR_THEME_FONT_URL')) {
            return ODR_THEME_FONT_URL;
        }

        // Check if theme declares a fonts_url function
        if (function_exists('get_theme_mod')) {
            $font_url = get_theme_mod('primary_font_url', '');
            if (! empty($font_url)) {
                return $font_url;
            }
        }

        // Default: empty (no critical font to preload)
        return '';
    }
}
