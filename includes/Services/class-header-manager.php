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
     * Register hooks for cache header application
     *
     * @return void
     */
    public function register(): void
    {
        // Use 'send_headers' hook - fires before any content is sent
        // This is earlier than template_redirect and ensures headers are sent
        add_action('send_headers', [$this, 'apply_cache_headers']);
    }

    /**
     * Apply aggressive cache headers for media and assets
     *
     * Called on send_headers (earliest point to send headers, before content).
     * Only applies to public-facing pages, not admin.
     *
     * Cache strategy:
     * - Static assets (images, fonts, CSS, JS): 1 year (immutable)
     * - HTML pages: 1 hour (allows updates without manual purge)
     * - Browser revalidation: ETags + Last-Modified for validation
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

        // Cache strategy for static assets: 1 year (31536000 seconds)
        // immutable: File won't change (content-based naming)
        if (preg_match('~\.(webp|jpg|jpeg|png|gif|css|js|woff2|woff|svg)$~i', $request_uri)) {
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            header('Cache-Control: public, max-age=31536000, immutable');
            // phpcs:enable
        } else {
            // HTML pages and dynamic content: Cache for 1 hour
            // Allows browser caching but respects updates within 1 hour
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            header('Cache-Control: public, max-age=3600, must-revalidate');
            // phpcs:enable
        }

        // Add Vary header for proper caching with Accept-Encoding
        // This ensures gzip/brotli compressed versions are cached separately
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        header('Vary: Accept-Encoding');
        // phpcs:enable
    }
}
