<?php

declare(strict_types=1);

/**
 * Compatibility Service
 *
 * Ensures WebP versions are included in WordPress srcset generation.
 * Hooks into wp_calculate_image_srcset to redirect subsizes to .webp files
 * when WebP conversion has occurred.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Compatibility_Service: WebP srcset restoration
 *
 * When MetadataManager registers a WebP size in attachment metadata,
 * WordPress generates a srcset for it. This service ensures the srcset
 * URLs point to the actual .webp files we created.
 */
class Compatibility_Service
{
    /**
     * Optional settings repository for future configuration
     *
     * @var Settings_Repository|null
     * @phpstan-ignore-next-line unused
     */
    private ?Settings_Repository $settings;

    /**
     * Constructor
     *
     * @param Settings_Repository|null $settings Optional settings for future use.
     */
    public function __construct(?Settings_Repository $settings = null)
    {
        $this->settings = $settings;
    }

    /**
     * Register hooks for srcset compatibility
     *
     * @return void
     */
    public function register(): void
    {
        \add_filter(
            'wp_calculate_image_srcset',
            [ $this, 'restore_srcset_with_webp' ],
            10,
            5,
        );

        // Tighten sizes attribute to match theme content width (704px)
        \add_filter(
            'wp_calculate_image_sizes',
            [ $this, 'tighten_image_sizes' ],
            10,
            5,
        );
    }

    /**
     * Restore srcset with WebP URLs
     *
     * WordPress calculates srcset for all registered sizes in metadata.
     * If the original image was converted to WebP, we need to ensure
     * the srcset includes the WebP file paths, not the original JPEG/PNG.
     *
     * Filter Signature:
     * apply_filters('wp_calculate_image_srcset', $sources, $size_array, $image_src, $image_meta, $attachment_id)
     *
     * @param array<int, array<string, mixed>> $sources        Array of size => URL pairs for srcset.
     * @param array<int, int>                  $sizeArray     The requested image size [width, height].
     * @param string                            $imageSrc       The image source URL (original file).
     * @param array<string, mixed>              $imageMeta      The attachment metadata.
     * @param int                               $attachmentId   The attachment post ID.
     *
     * @return array<int, array<string, mixed>> Modified sources with WebP paths where applicable.
     */
    public function restore_srcset_with_webp(
        array $sources,
        array $sizeArray,
        string $imageSrc,
        array $imageMeta,
        int $attachmentId,
    ): array {
        // Only process if original was converted to WebP
        if (! \str_contains($imageSrc, '.webp')) {
            return $sources;
        }

        // Update each srcset entry to point to WebP version
        foreach ($sources as &$source) {
            // Ensure we have a valid URL string
            if (empty($source['url']) || ! is_string($source['url'])) {
                continue;
            }

            // Replace common image extensions with .webp
            $source['url'] = \str_replace(
                [ '.jpg', '.jpeg', '.png' ],
                '.webp',
                $source['url'],
            );
        }

        return $sources;
    }

    /**
     * Tighten image sizes attribute for theme content width
     *
     * WordPress generates generic sizes attributes like "100vw" which can
     * cause Lighthouse to flag "Images are not properly sized" if the browser
     * downloads a version slightly larger than the container.
     *
     * This filter ensures sizes exactly match the theme's 704px content width.
     *
     * Filter Signature:
     * apply_filters('wp_calculate_image_sizes', $sizes, $size_array, $image_src, $image_meta, $attachment_id)
     *
     * @param string                  $sizes        The sizes attribute string (may be empty).
     * @param array<int, int>         $sizeArray    The image dimensions [width, height].
     * @param string                  $imageSrc     The image source URL.
     * @param array<string, mixed>    $imageMeta    The attachment metadata.
     * @param int                     $attachmentId The attachment post ID.
     *
     * @return string Modified sizes attribute for 704px content width.
     */
    public function tighten_image_sizes(
        string $sizes,
        array $sizeArray,
        string $imageSrc,
        array $imageMeta,
        int $attachmentId,
    ): string {
        // Only apply to singular posts in main content area
        if (! \is_singular()) {
            return $sizes;
        }

        // Check if this is our optimized size (704px width)
        // Only tighten if image width is 704px (content width)
        if (isset($sizeArray[0]) && (int) $sizeArray[0] === 704) {
            // Cap at 704px to match Twenty Twenty-Five content width
            return '(max-width: 704px) 100vw, 704px';
        }

        return $sizes;
    }
}
