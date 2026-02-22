<?php

declare(strict_types=1);

/**
 * Server Service
 *
 * Responsible for the server-to-client "pipe" optimization.
 * Ensures the HTTP connection itself is fast through gzip compression
 * and proper caching headers.
 *
 * Single Responsibility: Environment & Transport Layer
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Server_Service: Optimize the HTTP transport layer
 *
 * Focuses solely on compression and headers.
 * Does NOT handle content optimizations (that's Asset_Service, Image_Service, etc).
 */
class Server_Service
{
    /**
     * Register hooks for server-level optimizations
     *
     * @return void
     */
    public function register(): void
    {
        add_action('init', [$this, 'enable_compression'], 1);
        add_filter('wp_headers', [$this, 'add_cache_headers'], 10, 1);
    }

    /**
     * Enable gzip compression at the server level
     *
     * Compresses the entire HTML response before sending to browser.
     * Reduces bandwidth by 60-80% for text-heavy responses.
     *
     * Guards:
     * - Skip in admin (unnecessary for admin pages)
     * - Skip if headers already sent (prevents headers_already_sent() errors)
     * - Skip if zlib.output_compression already enabled (prevents double compression)
     * - Skip if client doesn't support gzip (check Accept-Encoding header)
     *
     * @return void
     */
    public function enable_compression(): void
    {
        // Skip in admin area
        if (is_admin()) {
            return;
        }

        // Skip if headers already sent
        if (headers_sent()) {
            return;
        }

        // Skip if PHP already compressing output
        if (ini_get('zlib.output_compression')) {
            return;
        }

        // Check if client supports gzip
        if (! isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            return;
        }

        if (! str_contains($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) {
            return;
        }

        // Enable gzip compression using PHP's built-in handler
        ob_start('ob_gzhandler');
    }

    /**
     * Add Cache-Control headers for static assets
     *
     * Forces proper cache headers for assets served through WordPress.
     * Lighthouse checks for these headers to validate caching strategy.
     *
     * Assets with version query strings (?ver=...) are treated as immutable
     * since version changes result in new filenames.
     *
     * @param array<string, string> $headers The HTTP headers array
     * @return array<string, string>         Modified headers with cache-control
     */
    public function add_cache_headers(array $headers): array
    {
        // Skip if no REQUEST_URI (shouldn't happen but be safe)
        if (! isset($_SERVER['REQUEST_URI'])) {
            return $headers;
        }

        // Get file extension from request URI
        $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        $extension = strtolower(pathinfo($request_uri, PATHINFO_EXTENSION));

        // Apply cache headers to static assets
        // These are the file types that Lighthouse checks for caching
        if (in_array($extension, ['woff2', 'woff', 'ttf', 'otf', 'webp', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'js', 'css'], true)) {
            // Cache for 1 year, immutable (files change by name, not content)
            $headers['Cache-Control'] = 'public, max-age=31536000, immutable';
        }

        return $headers;
    }
}
