<?php

declare(strict_types=1);

/**
 * Layout Policy Service
 *
 * Determines responsive image sizing based on the theme's layout configuration.
 * Uses Dependency Inversion: depend on WordPress config, not hardcoded values.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Calculates ideal image width based on theme settings
 *
 * Reads the global $content_width variable that themes define to specify
 * their maximum content area width. This ensures responsive sizing adapts
 * to any theme layout without plugin configuration.
 */
class LayoutPolicy
{
    /**
     * Determine the maximum content width allowed by the theme
     *
     * Falls back to 1024px if theme doesn't define $content_width.
     * Most themes define this in their functions.php.
     *
     * Examples:
     * - Twenty Twenty-Five: 645px
     * - Twenty Twenty-Four: 768px
     * - Twenty Twenty-Three: 670px
     * - Fallback: 1024px
     *
     * @return int Maximum content width in pixels.
     */
    public function get_max_content_width(): int
    {
        global $content_width;

        // If theme defines $content_width, use it
        if (! empty($content_width)) {
            return (int) $content_width;
        }

        // Fallback to 1024px (conservative default)
        return 1024;
    }

    /**
     * Generate the 'sizes' attribute string for responsive images
     *
     * Creates a media query that tells the browser:
     * - On small viewports (≤ width): image is 100% of viewport width
     * - On larger viewports: image is capped at the specified width
     *
     * Example output: "(max-width: 645px) 100vw, 645px"
     * This means: "On screens up to 645px, use 100% of screen width.
     *             On larger screens, use 645px maximum."
     *
     * @param int $width The maximum content width in pixels.
     * @return string The sizes attribute value for responsive image.
     */
    public function generate_sizes_attribute(int $width): string
    {
        return sprintf('(max-width: %1$dpx) 100vw, %1$dpx', $width);
    }

    /**
     * Calculate padding for safe image delivery
     *
     * Adds a small buffer to account for margins/padding in the layout.
     * Most themes use 15-30px padding per side, so we subtract 60px total.
     *
     * @param int $content_width The theme's content width.
     * @return int Adjusted width accounting for layout padding.
     */
    public function get_effective_width(int $content_width): int
    {
        // Subtract padding/margin buffer (60px total for padding on both sides)
        $effective_width = $content_width - 60;

        // Never go below 300px (minimum sensible mobile width)
        return max(300, $effective_width);
    }
}
