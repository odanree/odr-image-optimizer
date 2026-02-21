<?php

declare(strict_types=1);

/**
 * Size Registry Service
 *
 * Defines and manages custom image sizes for optimal responsive image delivery.
 * Bridges the gap between mobile and tablet breakpoints while maintaining SOLID principles.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Custom image size registry for responsive optimization
 *
 * Registers sizes that fill gaps in WordPress's default thumbnail sizes,
 * ensuring mobile devices receive appropriately-sized images without over-delivery.
 */
class SizeRegistry
{
    /**
     * Define custom image sizes optimized for the theme's specific layout
     *
     * Instead of hardcoding sizes, this reads the theme's $content_width
     * and registers sizes that match the actual layout exactly.
     *
     * This ensures:
     * - Desktop gets the exact size the theme needs
     * - Retina displays (2x/3x) get appropriately upscaled versions
     * - Mobile devices get responsive variants without waste
     *
     * Example (Twenty Twenty-Five):
     * - Theme width: 645px
     * - Registered: odr_content_optimized (645px)
     * - Registered: odr_content_retina (1290px for 2x displays)
     */
    public function register_optimized_sizes(): void
    {
        global $content_width;

        // Use the theme's defined width as the "Gold Standard"
        // This ensures we register sizes that exactly match the layout
        $target_width = ! empty($content_width) ? (int) $content_width : 704;

        // Register a size that is EXACTLY what the theme needs
        // This size becomes the "primary" responsive option
        add_image_size('odr_content_optimized', $target_width, 0, false);

        // Register a "Retina" version (2x) for high-DPI mobile/desktop
        // Ensures crisp rendering on 2x and 3x displays
        add_image_size('odr_content_retina', $target_width * 2, 0, false);

        // Keep the mobile-optimized sizes for small viewports
        // 450px bridges the gap for 365px-400px viewports
        add_image_size('odr_mobile_optimized', 450, 0, false);

        // 600px bridges mobile to tablet
        add_image_size('odr_tablet_optimized', 600, 0, false);
    }

    /**
     * Ensure custom sizes are included in srcset calculations
     *
     * WordPress calculates srcset based on intermediate image sizes.
     * This filter ensures our custom theme-aware sizes are available for responsive calculation.
     *
     * @param array<string, array<string, mixed>> $sizes Image size data for srcset calculation.
     * @return array<string, array<string, mixed>> Modified sizes array with custom sizes included.
     */
    public function add_to_srcset(array $sizes): array
    {
        global $content_width;

        // Calculate the theme-aware sizes
        $target_width = ! empty($content_width) ? (int) $content_width : 704;

        // Add our dynamically-registered custom sizes to the srcset calculation
        $custom_sizes = [
            'odr_content_optimized' => [
                'width'  => $target_width,
                'height' => 0,
                'crop'   => false,
            ],
            'odr_content_retina'     => [
                'width'  => $target_width * 2,
                'height' => 0,
                'crop'   => false,
            ],
            'odr_mobile_optimized'   => [
                'width'  => 450,
                'height' => 0,
                'crop'   => false,
            ],
            'odr_tablet_optimized'   => [
                'width'  => 600,
                'height' => 0,
                'crop'   => false,
            ],
        ];

        return array_merge($sizes, $custom_sizes);
    }

    /**
     * Get the container width recommendation for frontend delivery
     *
     * Reads the global $content_width variable that themes define.
     * This ensures the plugin adapts to any theme layout automatically.
     *
     * Used by LayoutPolicy and SizeSelector to determine the optimal image size.
     *
     * @return int Theme's content width in pixels. Defaults to 704px if not defined.
     */
    public function get_container_width(): int
    {
        global $content_width;

        // If theme defines $content_width, use it
        if (! empty($content_width)) {
            return (int) $content_width;
        }

        // Fallback to 704px (conservative default)
        // This is the median width for most WordPress themes
        return 704;
    }
}
