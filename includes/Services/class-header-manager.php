<?php

declare(strict_types=1);

/**
 * HTTP Header Manager Service
 *
 * Manages cache-control and performance headers for optimized delivery.
 * Ensures browsers cache images/assets aggressively while respecting CDN strategies.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Injects Cache-Control headers for long-term caching
 *
 * Tells browsers (and CDNs) to cache images, WebP, and assets for 1 year.
 * Uses immutable flag because these files have content-based names (won't change).
 *
 * Example:
 * - yosemite-unsplash-704x470.webp → Cache 1 year
 * - circle-grass-unsplash-1408x939.webp → Cache 1 year
 *
 * If file changes, WordPress updates the filename → new file = new cache key
 */
class HeaderManager
{
    /**
     * Apply aggressive cache headers for media and assets
     *
     * Called on template_redirect (early in request, before content output).
     * Only applies to public-facing pages, not admin.
     *
     * @return void
     */
    public function apply_cache_headers(): void
    {
        // Only apply to frontend
        if (is_admin()) {
            return;
        }

        // Check if this is a request for a media file or static asset
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        // Only cache actual files (images, webp, css, js), not HTML pages
        if (preg_match('~\.(webp|jpg|jpeg|png|gif|css|js|woff2|woff)$~i', $request_uri)) {
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            // Aggressive cache for 1 year (31536000 seconds)
            // immutable: File won't change (content-based naming)
            header('Cache-Control: public, max-age=31536000, immutable');
            // phpcs:enable
        }
    }
}
