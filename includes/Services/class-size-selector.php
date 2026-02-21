<?php

declare(strict_types=1);

/**
 * Size Selector Service
 *
 * Intelligently selects the optimal image size based on container width.
 * Ensures browser receives properly scaled images without pixelation.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Strategy Pattern: Select optimal image size based on rendered width
 *
 * Prevents pixelation by ensuring the selected size is always >= container width.
 * This allows proper downscaling on the browser side while keeping file sizes small.
 */
class SizeSelector
{
    /**
     * Find the smallest sub-size that is still larger than the container
     *
     * @param int $rendered_width The container width in pixels.
     * @param int $attachment_id  The attachment ID.
     * @return string The optimal size slug (e.g., 'medium_large', 'large', 'full').
     */
    public function get_optimal_size_slug(int $rendered_width, int $attachment_id): string
    {
        $metadata = wp_get_attachment_metadata($attachment_id);

        if (empty($metadata['sizes'])) {
            return 'full';
        }

        $sizes = $metadata['sizes'];

        // Sort sizes by width ascending
        uasort(
            $sizes,
            function ($a, $b) {
                return ($a['width'] ?? 0) <=> ($b['width'] ?? 0);
            }
        );

        // Find the first size that is >= the rendered width
        // We want the image to be at least as wide as the container
        // to avoid pixelation from upscaling
        foreach ($sizes as $slug => $data) {
            $size_width = (int) ($data['width'] ?? 0);

            // Return the first size that is >= rendered width
            // This ensures proper downscaling on the browser
            if ($size_width >= $rendered_width) {
                return (string) $slug;
            }
        }

        // If no size is large enough, return full
        return 'full';
    }

    /**
     * Get the primary image URL for the optimal size
     *
     * @param int    $attachment_id The attachment ID.
     * @param int    $rendered_width The container width in pixels.
     * @param string $fallback_size  Fallback size if selector fails.
     * @return string The image URL.
     */
    public function get_optimal_image_url(int $attachment_id, int $rendered_width, string $fallback_size = 'large'): string
    {
        $size_slug = $this->get_optimal_size_slug($rendered_width, $attachment_id);
        return wp_get_attachment_image_url($attachment_id, $size_slug) ?: wp_get_attachment_image_url($attachment_id, $fallback_size);
    }

    /**
     * Get responsive srcset for the optimal size
     *
     * @param int    $attachment_id The attachment ID.
     * @param int    $rendered_width The container width in pixels.
     * @param string $fallback_size  Fallback size if selector fails.
     * @return string The srcset attribute value.
     */
    public function get_optimal_srcset(int $attachment_id, int $rendered_width, string $fallback_size = 'large'): string
    {
        $size_slug = $this->get_optimal_size_slug($rendered_width, $attachment_id);
        $srcset = wp_get_attachment_image_srcset($attachment_id, $size_slug);

        if (! $srcset) {
            $srcset = wp_get_attachment_image_srcset($attachment_id, $fallback_size);
        }

        return (string) $srcset;
    }

    /**
     * Get the sizes attribute for responsive behavior
     *
     * @param int $rendered_width The container width in pixels.
     * @return string The sizes attribute value.
     */
    public function get_sizes_attribute(int $rendered_width): string
    {
        return "(max-width: {$rendered_width}px) 100vw, {$rendered_width}px";
    }
}
