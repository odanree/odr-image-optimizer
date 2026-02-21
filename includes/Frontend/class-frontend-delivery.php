<?php

declare(strict_types=1);

/**
 * Frontend Delivery Service
 *
 * Strictly-typed optimization of image attributes for responsive delivery.
 * Intercepts WordPress image rendering to serve optimal WebP versions.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Frontend;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Decorates image attributes for performance optimization
 *
 * Overrides image attributes to serve the most optimized version.
 * Runs at priority 20 to ensure it executes after theme defaults.
 */
class FrontendDelivery
{
    /**
     * Overrides image attributes to serve the optimized WebP version
     *
     * Forces the plugin's optimized sizes (704px for desktop, responsive variants for mobile)
     * ensuring Lighthouse compliance and zero over-delivery.
     *
     * @param array<string, mixed> $attrs      Image attributes (src, srcset, sizes, width, height).
     * @param \WP_Post             $attachment The attachment post object.
     * @return array<string, mixed> Modified attributes with optimized src, srcset, and sizes.
     */
    public function serve_optimized_attributes(array $attrs, \WP_Post $attachment): array
    {
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

        return $attrs;
    }
}
