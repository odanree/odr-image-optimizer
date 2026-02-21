<?php

declare(strict_types=1);

/**
 * Responsive Image Service - Generates proper HTML with srcset/sizes
 *
 * Handles frontend rendering of responsive images with proper attributes.
 * Follows SRP (formatting) and OCP (easily extensible for new image sizes).
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Frontend;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Service for generating responsive image HTML
 *
 * Single Responsibility: Format attachment into proper responsive HTML
 * Open/Closed: Extensible for new image sizes, lazy loading, custom attributes
 */
class ResponsiveImageService
{
    /**
     * Generate responsive image HTML tag
     *
     * Uses WordPress native wp_get_attachment_image() which automatically
     * includes srcset, sizes, and other responsive attributes.
     *
     * @param int    $attachment_id WordPress attachment ID.
     * @param string $size Image size slug (default: 'medium').
     * @param array  $attr Additional attributes for img tag.
     * @return string HTML img tag with srcset and sizes attributes.
     */
    public static function render_responsive_image(
        int $attachment_id,
        string $size = 'medium',
        array $attr = []
    ): string {
        // Default attributes for responsive behavior
        $default_attr = [
            'style'   => 'width:100%;height:auto;object-fit:cover;',
            'loading' => 'lazy',  // Native lazy loading for Lighthouse performance
            'decoding' => 'async', // Async decode for better performance
            'class'   => 'responsive-image',
        ];

        // Merge with custom attributes (custom can override defaults)
        $merged_attr = array_merge($default_attr, $attr);

        // WordPress native function handles srcset + sizes automatically
        return wp_get_attachment_image($attachment_id, $size, false, $merged_attr);
    }

    /**
     * Get responsive image attributes only (no HTML tag)
     *
     * Useful when you need to apply attributes to a custom element.
     *
     * @param int    $attachment_id WordPress attachment ID.
     * @param string $size Image size slug.
     * @return array Array of attributes suitable for wp_parse_args().
     */
    public static function get_responsive_attributes(
        int $attachment_id,
        string $size = 'medium'
    ): array {
        $image_meta = wp_get_attachment_metadata($attachment_id);

        if (! $image_meta) {
            return [];
        }

        return [
            'src'     => wp_get_attachment_url($attachment_id),
            'srcset'  => wp_get_attachment_image_srcset($attachment_id, $size) ?: '',
            'sizes'   => wp_get_attachment_image_sizes($attachment_id, $size) ?: '',
            'alt'     => get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: '',
            'width'   => $image_meta['width'] ?? 0,
            'height'  => $image_meta['height'] ?? 0,
        ];
    }

    /**
     * Render picture element with WebP + fallback
     *
     * Advanced: Uses <picture> element for format selection
     * Browser downloads most appropriate format and size
     *
     * @param int    $attachment_id WordPress attachment ID.
     * @param string $size Image size slug.
     * @param array  $attr Additional img attributes.
     * @return string HTML picture element with WebP source and JPEG fallback.
     */
    public static function render_picture_element(
        int $attachment_id,
        string $size = 'medium',
        array $attr = []
    ): string {
        $jpg_srcset = wp_get_attachment_image_srcset($attachment_id, $size);
        $jpg_sizes = wp_get_attachment_image_sizes($attachment_id, $size);
        $jpg_url = wp_get_attachment_url($attachment_id);

        // If srcset is empty, manually build it from subsizes
        if (!$jpg_srcset) {
            $jpg_srcset = self::manually_build_srcset($attachment_id, 'jpg');
        }
        
        // If sizes is still empty, generate a default
        if (!$jpg_sizes) {
            $jpg_sizes = self::generate_default_sizes($attachment_id);
        }
        
        if (!$jpg_srcset || !$jpg_sizes) {
            // Fallback to simple responsive image
            return self::render_responsive_image($attachment_id, $size, $attr);
        }

        // Generate WebP srcset by replacing file extensions
        $webp_srcset = self::convert_srcset_to_webp($jpg_srcset);
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true) ?: '';

        // Default img attributes
        $img_attr = array_merge([
            'style'   => 'width:100%;height:auto;object-fit:cover;',
            'loading' => 'lazy',
            'decoding' => 'async',
        ], $attr);

        $attr_string = '';
        foreach ($img_attr as $key => $value) {
            $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return <<<HTML
<picture>
  <source type="image/webp" srcset="{$webp_srcset}" sizes="{$jpg_sizes}">
  <source type="image/jpeg" srcset="{$jpg_srcset}" sizes="{$jpg_sizes}">
  <img src="{$jpg_url}" alt="{$alt_text}"{$attr_string}>
</picture>
HTML;
    }

    /**
     * Manually build srcset from attachment subsizes
     *
     * Used when wp_get_attachment_image_srcset() returns empty.
     * Builds srcset from all available subsizes in attachment metadata.
     *
     * @param int    $attachment_id WordPress attachment ID.
     * @param string $format Image format ('jpg', 'webp').
     * @return string Responsive srcset attribute.
     */
    private static function manually_build_srcset(int $attachment_id, string $format = 'jpg'): string
    {
        $metadata = wp_get_attachment_metadata($attachment_id);

        if (!$metadata || !isset($metadata['sizes'])) {
            return '';
        }

        $srcset_parts = [];
        $base_url = wp_get_attachment_url($attachment_id);
        $base_dir = dirname($base_url);

        foreach ($metadata['sizes'] as $size_name => $size_data) {
            $width = $size_data['width'];
            $file = $size_data['file'];

            // Build URL for this size
            $size_url = $base_dir . '/' . $file;

            if ($format === 'webp') {
                $size_url .= '.webp';
            }

            $srcset_parts[] = "{$size_url} {$width}w";
        }

        return implode(', ', $srcset_parts);
    }

    /**
     * Generate default sizes attribute when WordPress doesn't provide one
     *
     * Creates responsive sizes for common container widths.
     *
     * @param int $attachment_id WordPress attachment ID.
     * @return string Sizes attribute value.
     */
    private static function generate_default_sizes(int $attachment_id): string
    {
        $metadata = wp_get_attachment_metadata($attachment_id);

        if (!$metadata) {
            return '';
        }

        // Get the largest subsize width
        $max_width = 0;
        if (isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size_data) {
                $max_width = max($max_width, $size_data['width']);
            }
        }

        if ($max_width === 0) {
            return '';
        }

        // Generate responsive sizes for typical layouts
        // Assumes image takes full width on mobile, ~80% on tablet, ~60% on desktop
        return "(max-width: 600px) 100vw, (max-width: 1200px) 80vw, 60vw";
    }

    /**
     * Convert JPEG srcset to WebP srcset
     *
     * Replaces file extensions in srcset while preserving width descriptors.
     *
     * @param string $jpeg_srcset Original JPEG srcset.
     * @return string WebP srcset.
     */
    private static function convert_srcset_to_webp(string $jpeg_srcset): string
    {
        // Pattern: filename.jpg 768w, filename-2.jpg 1536w
        // Replace: filename.jpg → filename.webp
        return (string) preg_replace(
            '/(\S+)\.(jpg|jpeg|png)(?=\s+\d+w)/i',
            '$1.webp',
            $jpeg_srcset
        ) ?: $jpeg_srcset;
    }

    /**
     * Check if image has optimized versions available
     *
     * @param int $attachment_id WordPress attachment ID.
     * @return bool True if optimized versions (subsizes, WebP) exist.
     */
    public static function has_optimized_versions(int $attachment_id): bool
    {
        $attached_file = get_attached_file($attachment_id);

        if (! $attached_file) {
            return false;
        }

        // Check if WebP version exists
        if (! file_exists($attached_file . '.webp')) {
            return false;
        }

        // Check if subsizes are optimized
        $metadata = wp_get_attachment_metadata($attachment_id);

        if (! isset($metadata['sizes']) || empty($metadata['sizes'])) {
            return false;
        }

        $base_dir = dirname($attached_file);
        $optimized_subsizes = 0;

        foreach ($metadata['sizes'] as $size_data) {
            $subsize_file = $base_dir . '/' . $size_data['file'];
            $subsize_webp = $subsize_file . '.webp';

            if (file_exists($subsize_webp)) {
                $optimized_subsizes++;
            }
        }

        return $optimized_subsizes > 0;
    }

    /**
     * Get loading optimization attributes
     *
     * Returns attributes for lazy loading and performance optimization.
     *
     * @param bool $lazy Enable native lazy loading.
     * @param bool $async Enable async decoding.
     * @return array Attributes array.
     */
    public static function get_performance_attributes(bool $lazy = true, bool $async = true): array
    {
        $attr = [];

        if ($lazy) {
            $attr['loading'] = 'lazy';
        }

        if ($async) {
            $attr['decoding'] = 'async';
        }

        return $attr;
    }
}
