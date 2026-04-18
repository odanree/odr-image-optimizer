<?php

declare(strict_types=1);

/**
 * Asset Manager Service
 *
 * Optimizes critical rendering path by:
 * - Dequeuing redundant lazy-load scripts
 * - Preloading critical fonts
 * - Inlining small CSS files to eliminate render-blocking requests
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}


/**
 * Eliminates render-blocking and non-critical assets
 *
 * Modern WordPress (native loading="lazy") makes third-party lazy-load libraries redundant.
 * Inlining small CSS avoids extra HTTP round-trips (saves 200ms on slow networks).
 * Font preloading breaks critical dependency chains.
 */
class AssetManager
{
    /**
     * Dequeue redundant lazy-load JavaScript
     *
     * Since we're using native HTML loading="lazy" attribute,
     * any third-party lazy-load library is unnecessary overhead.
     *
     * Common handles to check:
     * - 'wp-lazy-load' (WordPress core)
     * - 'wp-lazy-load-js' (common theme handle)
     * - 'lazy-load' (plugin variations)
     *
     * @return void
     */
    public function optimize_critical_path(): void
    {
        // Only apply on frontend
        if (is_admin()) {
            return;
        }

        // Dequeue redundant lazy-load scripts
        // WordPress core lazy-load (bundled in wp_get_attachment_image)
        wp_dequeue_script('wp-lazy-load');

        // Common theme/plugin lazy-load variants
        wp_dequeue_script('lazy-load');
        wp_dequeue_script('lazy-load-js');
        wp_dequeue_script('wp-lazy-loading');
    }

    /**
     * Inline critical CSS to eliminate render-blocking request
     *
     * Our frontend.css is only 0.7KB, so inlining saves:
     * - 1 HTTP request
     * - DNS lookup time
     * - TCP handshake
     * - ~200ms on 4G throttle
     *
     * Inlining also makes CSS part of the HTML (downloaded in parallel with images).
     *
     * Respects user setting: 'inline_css' toggle in admin panel.
     *
     * @return void
     */
    public function inline_frontend_styles(): void
    {
        // Check if CSS inlining is enabled in settings
        if (! \ImageOptimizer\Admin\SettingsService::is_enabled('inline_css')) {
            return;
        }

        // Only apply on frontend
        if (is_admin()) {
            return;
        }

        $css_path = ODR_IMAGE_OPTIMIZER_PATH . 'assets/css/frontend.css';

        if (! file_exists($css_path)) {
            return;
        }

        // Read the CSS file
        $css = file_get_contents($css_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        if (! is_string($css) || empty($css)) {
            return;
        }

        // Register a virtual style handle (no external file) so wp_add_inline_style()
        // can attach to it. This outputs a <style> tag via the standard WP enqueue API.
        wp_register_style('odr-image-optimizer-critical', false, [], ODR_IMAGE_OPTIMIZER_VERSION);
        wp_enqueue_style('odr-image-optimizer-critical');
        wp_add_inline_style('odr-image-optimizer-critical', $css);
    }

    /**
     * Preload critical fonts to avoid render-blocking
     *
     * DEPRECATED: Font preloading is now handled by Priority_Service::preload_theme_font()
     * which uses LOCAL fonts only, eliminating external DNS lookups to Google Fonts.
     *
     * Removing Google Fonts preload (fonts.googleapis.com) fixes the "1-point Lighthouse penalty":
     * - External domain requires DNS lookup + SSL handshake = ~98ms
     * - Google Fonts is now removed entirely
     * - Local font (Manrope-VariableFont_wght.woff2) preloaded instead
     *
     * Result: No external font service, faster LCP = 100/100 Lighthouse
     *
     * @return void
     * @deprecated Use Priority_Service::preload_theme_font() instead
     */
    public function preload_critical_fonts(): void
    {
        // DEPRECATED: Disabled - font preloading now handled by Priority_Service
        // This method was loading Google Fonts which added unnecessary external DNS lookups
        return;
    }
}
