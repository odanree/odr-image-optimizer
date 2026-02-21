<?php

declare(strict_types=1);

/**
 * Priority Service
 *
 * Detects the LCP (Largest Contentful Paint) image candidate
 * and injects a preload hint so the browser starts downloading it
 * before parsing the CSS.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * State machine for LCP detection and preloading
 *
 * Identifies the featured image as LCP candidate early in the request,
 * then injects a <link rel="preload"> tag so the browser starts fetching
 * the 704px WebP immediately, before CSS processing.
 *
 * Flow:
 * 1. template_redirect (early): Call detectLcpId() to find featured image
 * 2. wp_head (priority 1): Call injectPreload() to emit <link rel="preload">
 * 3. Browser sees preload → starts downloading 704px image immediately
 * 4. Browser processes CSS → by then, image is already downloading
 */
class PriorityService
{
    /**
     * Tracks the LCP image attachment ID for this page
     *
     * Static so it persists across multiple method calls in same request.
     * Null if no featured image (not LCP-eligible page).
     *
     * @var int|null
     */
    private static ?int $lcp_id = null;

    /**
     * Detect the LCP candidate before the page renders
     *
     * Called at template_redirect (early, before wp_head).
     * Checks if we're on a singular post/page and captures the featured image ID.
     *
     * @return void
     */
    public function detect_lcp_id(): void
    {
        // Only on singular posts/pages, not admin
        if (! is_singular()) {
            return;
        }

        // Get the featured image ID
        $thumbnail_id = get_post_thumbnail_id();

        // Store it for use in injectPreload()
        if ($thumbnail_id) {
            self::$lcp_id = (int) $thumbnail_id;
        }
    }

    /**
     * Inject a preload hint for the LCP image into the head
     *
     * Called at wp_head (priority 1, very early, before styles).
     * Tells the browser: "Start downloading this image now, don't wait for CSS."
     *
     * The preload link includes:
     * - as="image": Hint that this is an image resource
     * - imagesrcset: All responsive variants (450px, 600px, 704px, 1408px)
     * - imagesizes: Responsive sizes for browser to pick correct variant
     * - fetchpriority="high": High priority download
     *
     * Why this matters:
     * - Without preload: HTML → parse CSS → find image in HTML → download
     * - With preload: HTML → start image download immediately (parallel with CSS)
     * - Difference: 200-300ms saved on 4G
     *
     * @return void
     */
    public function inject_preload(): void
    {
        // No preload if no featured image
        if (null === self::$lcp_id) {
            return;
        }

        // Get the 704px variant (our optimized size)
        $src = wp_get_attachment_image_url(self::$lcp_id, 'odr_content_optimized');

        if (! is_string($src)) {
            return;
        }

        // Get the srcset for responsive loading
        $srcset = wp_get_attachment_image_srcset(self::$lcp_id, 'odr_content_optimized');
        $srcset_attr = is_string($srcset) ? esc_attr($srcset) : '';

        // Emit the preload link
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        printf(
            '<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="(max-width: 704px) 100vw, 704px" fetchpriority="high">' . "\n",
            esc_url($src),
            $srcset_attr,
        );
        // phpcs:enable
    }

    /**
     * Reset LCP state for testing or isolation
     *
     * @return void
     */
    public static function reset_lcp_id(): void
    {
        self::$lcp_id = null;
    }

    /**
     * Get the LCP ID for debugging
     *
     * @return int|null
     */
    public static function get_lcp_id(): ?int
    {
        return self::$lcp_id;
    }
}
