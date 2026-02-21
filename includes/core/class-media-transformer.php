<?php

declare(strict_types=1);

/**
 * Media Transformer - Response Decorator for Images
 *
 * Converts WordPress attachment data into SOLID-compliant responsive API payloads.
 * Follows Decorator Pattern (OCP) and Single Responsibility (SRP).
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Transforms WordPress attachment metadata into responsive image payloads
 *
 * Single Responsibility: Format attachment data for API responses
 * Open/Closed: Easily extend with new fields (AVIF, BlurHash, etc.) without changing API
 */
class MediaTransformer
{
    /**
     * Transform attachment into responsive payload
     *
     * @param int $attachment_id WordPress attachment ID.
     * @return array Structured responsive image payload.
     */
    public static function transform_attachment(int $attachment_id): array
    {
        $post = get_post($attachment_id);

        if (! $post || 'attachment' !== $post->post_type) {
            return [];
        }

        $attached_file = get_attached_file($attachment_id);
        $file_size = $attached_file ? filesize($attached_file) : 0;
        $history = Database::get_optimization_history($attachment_id);
        $is_optimized = ! empty($history) && (! isset($history->status) || $history->status !== 'reverted');

        return [
            'id'              => $attachment_id,
            'title'           => $post->post_title,
            'filename'        => basename($attached_file ?: ''),
            'url'             => wp_get_attachment_url($attachment_id),
            'alt'             => self::get_alt_text($attachment_id),
            'responsive'      => self::get_responsive_data($attachment_id),
            'size'            => $file_size,
            'optimized'       => $is_optimized,
            'webp_available'  => $attached_file && file_exists($attached_file . '.webp'),
            'optimization'    => $history ? self::format_history($history) : null,
        ];
    }

    /**
     * Get responsive image data (srcset, sizes, image sizes)
     *
     * @param int $attachment_id WordPress attachment ID.
     * @return array Responsive data including srcset, sizes, and available image dimensions.
     */
    private static function get_responsive_data(int $attachment_id): array
    {
        // Get all registered image sizes for this attachment
        $image_sizes = wp_get_registered_image_subsizes();
        $available_sizes = [];

        foreach (array_keys($image_sizes) as $size_name) {
            $srcset = wp_get_attachment_image_srcset($attachment_id, $size_name);
            $sizes = wp_get_attachment_image_sizes($attachment_id, $size_name);

            if ($srcset) {
                $available_sizes[$size_name] = [
                    'srcset' => $srcset,
                    'sizes'  => $sizes ?: '',
                ];
            }
        }

        // If no specific sizes, use default
        if (empty($available_sizes)) {
            $srcset = wp_get_attachment_image_srcset($attachment_id);
            $sizes = wp_get_attachment_image_sizes($attachment_id);

            if ($srcset) {
                $available_sizes['full'] = [
                    'srcset' => $srcset,
                    'sizes'  => $sizes ?: '',
                ];
            }
        }

        return $available_sizes ?: [];
    }

    /**
     * Get alt text for image
     *
     * @param int $attachment_id WordPress attachment ID.
     * @return string Alt text or empty string.
     */
    private static function get_alt_text(int $attachment_id): string
    {
        return (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    }

    /**
     * Format optimization history
     *
     * @param object $history History object from database.
     * @return array Formatted history.
     */
    private static function format_history(object $history): array
    {
        return [
            'original_size'    => $history->original_size ?? 0,
            'optimized_size'   => $history->optimized_size ?? 0,
            'compression_ratio' => $history->compression_ratio ?? 0,
            'savings'          => ($history->original_size ?? 0) - ($history->optimized_size ?? 0),
            'method'           => $history->method ?? 'unknown',
            'status'           => $history->status ?? 'completed',
            'webp_available'   => $history->webp_available ?? false,
            'optimized_at'     => $history->optimized_at ?? null,
        ];
    }

    /**
     * Transform multiple attachments
     *
     * @param array $attachment_ids Array of attachment IDs.
     * @return array Array of transformed attachments.
     */
    public static function transform_attachments(array $attachment_ids): array
    {
        return array_map(
            [self::class, 'transform_attachment'],
            $attachment_ids,
        );
    }

    /**
     * Get responsive srcset for a specific size
     *
     * Decorator method to enhance wp_get_attachment_image_srcset with WebP support.
     *
     * @param int    $attachment_id WordPress attachment ID.
     * @param string $size Image size name.
     * @return string Responsive srcset attribute.
     */
    public static function get_responsive_srcset(int $attachment_id, string $size = 'medium'): string
    {
        $srcset = wp_get_attachment_image_srcset($attachment_id, $size);

        // If srcset empty, try full size
        if (! $srcset) {
            $srcset = wp_get_attachment_image_srcset($attachment_id);
        }

        return $srcset ?: '';
    }

    /**
     * Get responsive sizes attribute for a specific size
     *
     * @param int    $attachment_id WordPress attachment ID.
     * @param string $size Image size name.
     * @return string Responsive sizes attribute.
     */
    public static function get_responsive_sizes(int $attachment_id, string $size = 'medium'): string
    {
        $sizes = wp_get_attachment_image_sizes($attachment_id, $size);

        // If sizes empty, try full size
        if (! $sizes) {
            $sizes = wp_get_attachment_image_sizes($attachment_id);
        }

        return $sizes ?: '';
    }
}
