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
     * @return void
     */
    public function inline_frontend_styles(): void
    {
        // Only apply on frontend
        if (is_admin()) {
            return;
        }

        $css_path = IMAGE_OPTIMIZER_PATH . 'assets/css/frontend.css';

        if (! file_exists($css_path)) {
            return;
        }

        // Read the CSS file
        $css = file_get_contents($css_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        if (! is_string($css) || empty($css)) {
            return;
        }

        // Inline directly into the head
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="odr-optimizer-inline-css">' . $css . '</style>' . "\n";
        // phpcs:enable
    }

    /**
     * Preload critical fonts to avoid render-blocking
     *
     * Tells the browser to start downloading the font file early,
     * in parallel with other resources, instead of discovering it
     * when parsing the theme CSS.
     *
     * This breaks the critical chain:
     * Before: HTML → Theme CSS → Font discovery → Font download
     * After:  HTML + Font download (parallel)
     *
     * @return void
     */
    public function preload_critical_fonts(): void
    {
        // Only apply on frontend
        if (is_admin()) {
            return;
        }

        // If your theme uses Google Fonts (Manrope, etc), preload here
        // Example for Manrope-V:
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" as="style" crossorigin>' . "\n";
        // phpcs:enable
    }
}
