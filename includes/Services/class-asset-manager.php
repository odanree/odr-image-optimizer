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

use ImageOptimizer\Admin\SettingsPolicy;

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
     * DEPRECATED: CSS inlining is now handled by Asset_Service in the new SOA.
     * This legacy method is kept for backward compatibility but does nothing.
     *
     * @return void
     */
    public function inline_frontend_styles(): void
    {
        // Handled by Asset_Service in Service-Oriented Architecture
        return;
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
     * Always uses font-display: swap to show fallback text while custom font loads.
     * This prevents Flash of Unstyled Text (FOUT) penalty in Lighthouse.
     *
     * @return void
     */
    public function preload_critical_fonts(): void
    {
        // Check if font preload is enabled in settings
        if (! SettingsPolicy::should_preload_fonts()) {
            return;
        }

        // Only apply on frontend
        if (is_admin()) {
            return;
        }

        // Always use font-display: swap for optimal Lighthouse scores
        // Shows fallback text while custom font downloads (prevents FOUT penalty)
        $font_url = 'https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap';

        // Preload with crossorigin (required for fonts)
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        printf(
            '<link rel="preload" href="%s" as="style" crossorigin>' . "\n",
            esc_url($font_url),
        );
        // phpcs:enable
    }
}
