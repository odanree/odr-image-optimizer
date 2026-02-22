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
        // Remove version query strings from scripts/styles for better caching
        // WordPress adds ?ver=X.X.X which prevents browsers from caching
        add_filter('script_loader_src', [$this, 'remove_query_strings'], 15, 1);
        add_filter('style_loader_src', [$this, 'remove_query_strings'], 15, 1);

        // Preload critical fonts early (priority 0 = before everything)
        // Only if font preloading is enabled
        if ($this->settings->is_enabled('preload_fonts')) {
            add_action('wp_head', [$this, 'preload_critical_fonts'], 0);
        }

        // Remove WordPress bloat late (priority 999 = after all plugins/themes enqueue)
        // Only if bloat removal is enabled (aggressive mode)
        if ($this->settings->is_enabled('remove_bloat') || $this->settings->is_aggressive_mode()) {
            add_action('wp_enqueue_scripts', [$this, 'remove_core_bloat'], 999);
        }
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
     * @param string $src The script or style source URL
     * @return string     The cleaned URL without query strings
     */
    public function remove_query_strings(string $src): string
    {
        if (strpos($src, '?') === false) {
            return $src;
        }

        // Remove everything after the ? (query string)
        return strtok($src, '?');
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
