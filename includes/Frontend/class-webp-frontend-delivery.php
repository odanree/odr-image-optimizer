<?php

declare(strict_types=1);

/**
 * WebP Delivery on Frontend
 *
 * Serves WebP images when supported by browser, with JPEG fallback.
 * Integrates with WordPress theme image rendering.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Frontend;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * WebP delivery for frontend posts
 *
 * Hooks into WordPress image rendering to serve WebP + responsive srcset.
 */
class WebPFrontendDelivery
{
    /**
     * Initialize frontend WebP delivery
     */
    public static function init(): void
    {
        // Filter wp_get_attachment_image_attributes to inject responsive srcset/sizes
        add_filter('wp_get_attachment_image_attributes', [self::class, 'inject_responsive_attributes'], 10, 2);

        // Filter the_content to serve responsive images with WebP
        add_filter('the_content', [self::class, 'filter_post_images'], 10);

        // Filter wp_get_attachment_image to include WebP support
        add_filter('wp_get_attachment_image', [self::class, 'add_webp_picture_element'], 10, 5);

        // Filter featured image rendering
        add_filter('post_thumbnail_html', [self::class, 'add_webp_to_thumbnail'], 10, 5);
    }

    /**
     * Inject responsive srcset and sizes attributes into attachment images
     *
     * This filter ensures that WordPress serves the appropriate size variants
     * and responsive attributes to the browser, enabling proper responsive image loading.
     *
     * @param array   $attrs      Image attributes.
     * @param WP_Post $attachment The attachment object.
     * @return array Modified attributes with srcset and sizes.
     */
    public static function inject_responsive_attributes(array $attrs, \WP_Post $attachment): array
    {
        $attachment_id = $attachment->ID;

        // Generate responsive srcset for medium_large size
        $srcset = wp_get_attachment_image_srcset($attachment_id, 'medium_large');

        if ($srcset) {
            $attrs['srcset'] = $srcset;

            // Add sizes attribute so browser knows the container width
            // This tells the browser: on viewports up to 645px, image is 100vw
            // on larger viewports, image is limited to 645px
            if (! isset($attrs['sizes']) || empty($attrs['sizes'])) {
                $attrs['sizes'] = '(max-width: 645px) 100vw, 645px';
            }
        }

        return $attrs;
    }

    /**
     * Filter post content images to serve responsive versions with WebP
     *
     * @param string $content The post content.
     * @return string Modified content with responsive images.
     */
    public static function filter_post_images(string $content): string
    {
        // Find all img tags in the content
        if (! preg_match_all('/<img[^>]+>/i', $content, $matches)) {
            return $content;
        }

        foreach ($matches[0] as $img_tag) {
            // Skip if already has srcset (already optimized)
            if (strpos($img_tag, 'srcset') !== false) {
                continue;
            }

            // Extract attachment ID from src or data attributes
            $attachment_id = self::extract_attachment_id($img_tag);

            if (! $attachment_id) {
                continue;
            }

            // Generate new responsive img tag with WebP support
            $new_img = ResponsiveImageService::render_picture_element(
                $attachment_id,
                'large',
                ['class' => 'wp-content-image'],
            );

            $content = str_replace($img_tag, $new_img, $content);
        }

        return $content;
    }

    /**
     * Add WebP support to wp_get_attachment_image calls
     *
     * Wraps standard image rendering in picture element.
     *
     * @param string $html The image HTML.
     * @param int    $attachment_id The attachment ID.
     * @param string $size The image size.
     * @param bool   $icon Whether this is an icon.
     * @param array  $attr The attributes.
     * @return string HTML with WebP support.
     */
    public static function add_webp_picture_element(
        string $html,
        int $attachment_id,
        string $size,
        bool $icon,
        array $attr,
    ): string {
        // Skip if no image file
        if ($icon || ! $attachment_id) {
            return $html;
        }

        $attached_file = get_attached_file($attachment_id);

        // Only add picture element if WebP version exists
        if (! $attached_file || ! file_exists($attached_file . '.webp')) {
            return $html;
        }

        // Extract srcset/sizes from existing img tag
        preg_match('/srcset="([^"]*)"/', $html, $srcset_match);
        preg_match('/sizes="([^"]*)"/', $html, $sizes_match);

        $srcset = $srcset_match[1] ?? '';
        $sizes = $sizes_match[1] ?? '';

        if (! $srcset || ! $sizes) {
            // No srcset found, use picture element approach
            return ResponsiveImageService::render_picture_element($attachment_id, $size, $attr);
        }

        // Build WebP srcset
        $webp_srcset = self::convert_srcset_to_webp($srcset);

        // Extract src URL
        preg_match('/src="([^"]*)"/', $html, $src_match);
        $src_url = $src_match[1] ?? wp_get_attachment_url($attachment_id);

        // Extract alt text
        preg_match('/alt="([^"]*)"/', $html, $alt_match);
        $alt = $alt_match[1] ?? '';

        // Build picture element
        $picture = sprintf(
            '<picture>' .
            '<source type="image/webp" srcset="%s" sizes="%s">' .
            '<source type="image/jpeg" srcset="%s" sizes="%s">' .
            '%s' .
            '</picture>',
            esc_attr($webp_srcset),
            esc_attr($sizes),
            esc_attr($srcset),
            esc_attr($sizes),
            $html,
        );

        return $picture;
    }

    /**
     * Add WebP support to featured images
     *
     * @param string $html The image HTML.
     * @param int    $post_id The post ID.
     * @param int    $attachment_id The attachment ID.
     * @param string $size The image size.
     * @param mixed  $attr The attributes (can be string or array).
     * @return string HTML with WebP support.
     */
    public static function add_webp_to_thumbnail(
        string $html,
        int $post_id,
        int $attachment_id,
        string $size,
        $attr = [],
    ): string {
        $attr_array = is_array($attr) ? $attr : [];
        return self::add_webp_picture_element($html, $attachment_id, $size, false, $attr_array);
    }

    /**
     * Extract attachment ID from img tag
     *
     * Looks for attachment ID in various attributes/classes.
     *
     * @param string $img_tag The img tag HTML.
     * @return int|null Attachment ID or null.
     */
    private static function extract_attachment_id(string $img_tag): ?int
    {
        // Try to extract from wp-image-{id} class
        if (preg_match('/wp-image-(\d+)/', $img_tag, $matches)) {
            return (int) $matches[1];
        }

        // Try to extract from data-attachment-id attribute
        if (preg_match('/data-attachment-id="?(\d+)"?/', $img_tag, $matches)) {
            return (int) $matches[1];
        }

        // Try to extract from src URL
        if (preg_match('/\/uploads\/[\d\/]+\/.*\.(jpg|jpeg|png|gif)/', $img_tag, $matches)) {
            // Find attachment by file path
            global $wpdb;
            $file_path = $matches[0];

            $attachment_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts WHERE guid LIKE %s AND post_type = 'attachment'",
                    '%' . $file_path . '%',
                ),
            );

            return $attachment_id ? (int) $attachment_id : null;
        }

        return null;
    }

    /**
     * Convert JPEG srcset to WebP srcset
     *
     * @param string $jpeg_srcset Original JPEG srcset.
     * @return string WebP srcset.
     */
    private static function convert_srcset_to_webp(string $jpeg_srcset): string
    {
        return (string) preg_replace(
            '/(\S+\.(jpg|jpeg|png))(?=\s+\d+w)/i',
            '$1.webp',
            $jpeg_srcset,
        );
    }
}
