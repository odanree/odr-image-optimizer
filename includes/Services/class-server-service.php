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
}
