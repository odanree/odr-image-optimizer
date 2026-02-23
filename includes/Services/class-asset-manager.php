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
     * DEPRECATED: This method is no longer used!
     *
     * Font preloading is now handled by Asset_Service::preload_critical_fonts()
     * which uses LOCAL fonts only (no external Google Fonts).
     *
     * REMOVED: Google Fonts external URL was causing "Double Font Conflict":
     * - External: fonts.googleapis.com (DNS lookup + SSL handshake = 98ms long task)
     * - Local: localhost:8000/.../Manrope-VariableFont_wght.woff2 (Asset_Service)
     *
     * Every external domain lookup stalls rendering. By using local fonts only,
     * we eliminate DNS + SSL overhead and improve LCP by ~98ms.
     *
     * Asset_Service preloads the same font from the theme's local directory,
     * which is much faster and doesn't require external DNS lookups.
     *
     * @return void
     * @deprecated Use Asset_Service::preload_critical_fonts() instead
     */
    public function preload_critical_fonts(): void
    {
        // DEPRECATED: Asset_Service handles all font preloading with LOCAL files.
        // This method does nothing. Kept for backward compatibility only.
        return;
    }
}
