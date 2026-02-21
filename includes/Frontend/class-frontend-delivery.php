<?php

declare(strict_types=1);

/**
 * Frontend Delivery Service
 *
 * Strictly-typed optimization of image attributes for responsive delivery.
 * Intercepts WordPress image rendering to serve optimal WebP versions.
 * Manages LCP (Largest Contentful Paint) priority for first image.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Frontend;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Decorates image attributes for performance and LCP optimization
 *
 * Overrides image attributes to serve the most optimized version.
 * Tracks image processing to apply LCP priority to the first image.
 * Runs at priority 20 to ensure it executes after theme defaults.
 */
class FrontendDelivery
{
    /**
     * Tracks the number of images processed in the current request
     *
     * Used to determine LCP candidate (first image).
     * Static so it persists across multiple filter calls in same request.
     *
     * @var int
     */
    private static int $image_count = 0;

    /**
     * Overrides image attributes to serve the optimized WebP version
     *
     * Forces the plugin's optimized sizes (704px for desktop, responsive variants for mobile)
     * ensuring Lighthouse compliance and zero over-delivery.
     *
     * Applies LCP priority (eager loading, high fetch priority) to the first image,
     * allowing browsers to begin downloading before layout calculation.
     *
     * @param array<string, mixed> $attrs      Image attributes (src, srcset, sizes, width, height).
     * @param \WP_Post             $attachment The attachment post object.
     * @return array<string, mixed> Modified attributes with optimized src, srcset, sizes, and LCP hints.
     */
    public function serve_optimized_attributes(array $attrs, \WP_Post $attachment): array
    {
        self::$image_count++;
        $is_lcp_candidate = 1 === self::$image_count;

        $attachment_id = $attachment->ID;

        // Force the theme-optimized size (704px) as the primary source
        $optimized_url = wp_get_attachment_image_url($attachment_id, 'odr_content_optimized');
        if (is_string($optimized_url) && ! empty($optimized_url)) {
            $attrs['src'] = $optimized_url;
        }

        // Set srcset to include all responsive variants (450px, 600px, 704px, 1408px, etc.)
        $srcset = wp_get_attachment_image_srcset($attachment_id, 'odr_content_optimized');
        if (is_string($srcset) && ! empty($srcset)) {
            $attrs['srcset'] = $srcset;
        }

        // Explicitly set the sizes attribute for browser
        // This tells the browser: on small screens use 100vw, on large screens cap at 704px
        $attrs['sizes'] = '(max-width: 704px) 100vw, 704px';

        // Update intrinsic dimensions to match the optimized size
        // Get metadata to find exact dimensions of odr_content_optimized
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (is_array($metadata) && isset($metadata['sizes']['odr_content_optimized'])) {
            $size_data = $metadata['sizes']['odr_content_optimized'];
            $attrs['width'] = (int) ($size_data['width'] ?? 704);
            $attrs['height'] = (int) ($size_data['height'] ?? 469);
        } else {
            // Fallback to safe defaults if metadata unavailable
            $attrs['width'] = 704;
            $attrs['height'] = 469;
        }

        // HANDLE LCP PRIORITY
        // The first image on a page is likely the LCP (Largest Contentful Paint)
        // Eager-load it with high priority to reduce LCP time by 200-400ms
        if ($is_lcp_candidate) {
            // VIP Treatment: Tell the browser to download THIS NOW
            // fetchpriority=high: Start downloading before other resources
            // loading=eager: Don't lazy-load, fetch immediately
            // decoding=sync: Block rendering until image is decoded (LCP image should be sync)
            $attrs['fetchpriority'] = 'high';
            $attrs['loading'] = 'eager';
            $attrs['decoding'] = 'sync';
        } else {
            // Standard Treatment: Load when needed
            // loading=lazy: Let the browser decide when to fetch (usually on scroll)
            // decoding=async: Don't block rendering, decode in background
            $attrs['loading'] = 'lazy';
            $attrs['decoding'] = 'async';
        }

        return $attrs;
    }

    /**
     * Reset the image counter for testing or manual request isolation
     *
     * Called at the start of each page render or for unit testing.
     * Not normally needed in production (PHP lifecycle resets per request).
     */
    public static function reset_image_count(): void
    {
        self::$image_count = 0;
    }

    /**
     * Get the current image count for debugging
     *
     * @return int Number of images processed in the current request.
     */
    public static function get_image_count(): int
    {
        return self::$image_count;
    }
}
