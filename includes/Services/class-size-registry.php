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
     * Define custom image sizes optimized for mobile/tablet devices
     *
     * These sizes are registered with WordPress to ensure they're generated
     * when images are uploaded or optimized.
     *
     * - odr_mobile_optimized (450px): Fills gap between 300px and 768px
     *   Perfect for 365px-400px viewports (mobile landscape, small tablets)
     *
     * - odr_tablet_optimized (600px): Bridges mobile to tablet
     *   Optimal for 550px-700px viewports before full 768px desktop size
     */
    public function register_optimized_sizes(): void
    {
        // The "Mobile Hero" size - perfect for 365px-400px viewports
        add_image_size('odr_mobile_optimized', 450, 0, false);

        // The "Tablet" size - bridges mobile to 768px desktop size
        add_image_size('odr_tablet_optimized', 600, 0, false);
    }

    /**
     * Ensure custom sizes are included in srcset calculations
     *
     * WordPress calculates srcset based on intermediate image sizes.
     * This filter ensures our custom sizes are available for the responsive calculation.
     *
     * @param array $sizes Image size data for srcset calculation.
     * @return array Modified sizes array with custom sizes included.
     */
    public function add_to_srcset(array $sizes): array
    {
        // Add our custom sizes to the srcset calculation
        // This ensures browsers receive these width options in the responsive image tag
        $custom_sizes = [
            'odr_mobile_optimized' => [
                'width'  => 450,
                'height' => 0,
                'crop'   => false,
            ],
            'odr_tablet_optimized'  => [
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
     * Used by SizeSelector to determine the optimal image size based on layout.
     * Can be overridden via filter for custom layouts.
     *
     * @return int Container width in pixels.
     */
    public function get_container_width(): int
    {
        /**
         * Filter to customize the responsive image container width
         *
         * @param int $width Default width (645px for post content).
         * @return int Modified width in pixels.
         */
        return (int) apply_filters('odr_container_width', 645);
    }
}
