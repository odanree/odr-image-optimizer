<?php

declare(strict_types=1);




if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * WebP Delivery - Serves WebP images when available and browser supports it
 *
 * @package ImageOptimizer\Frontend
 * @author  Danh Le
 */

namespace ImageOptimizer\Frontend;

/**
 * WebP Delivery class - Handles WebP format serving to browsers
 */
class WebpDelivery
{
    private bool $enabled = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void
    {
        // Check if WebP delivery is enabled in settings
        $settings = get_option('image_optimizer_settings', []);
        $this->enabled = ! empty($settings['enable_webp']);

        if (!$this->enabled) {
            return;
        }

        // Hook into content rendering to replace image URLs dynamically
        // Run at priority 7 (before wpautop at 10) to catch images before DOM manipulation
        add_filter('the_content', [ $this, 'replace_images_with_webp' ], 7);

        // Hook into widget content
        add_filter('widget_text', [ $this, 'replace_images_with_webp' ], 7);

        // Hook into post excerpts
        add_filter('the_excerpt', [ $this, 'replace_images_with_webp' ], 7);
    }

    /**
     * Replace image URLs with WebP versions in content
     *
     * @param string $content The post content.
     * @return string
     */
    public function replace_images_with_webp(string $content): string
    {
        if (!$this->browser_supports_webp()) {
            return $content;
        }

        // Pattern to find img src attributes - more flexible
        $pattern = '/src=["\']([^"\']*(?:uploads|wp-content\/uploads)[^\'"]*\.(jpg|jpeg|png))(["\'])/i';

        return preg_replace_callback($pattern, function ($matches) {
            $original_url = $matches[1];
            $extension = $matches[2];
            $quote = $matches[3];

            // Extract filename from URL
            preg_match('/\/([^\/]+)\.(jpg|jpeg|png)$/i', $original_url, $filename_match);
            if (!$filename_match) {
                return $matches[0]; // Can't parse filename, return original
            }

            $filename = $filename_match[1];

            // Build WebP path
            $webp_url = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $original_url);

            // Check if WebP file exists on disk
            $upload_dir = wp_upload_dir();
            $webp_path = $upload_dir['basedir'] . '/' . $filename . '.webp';

            if (!file_exists($webp_path)) {
                return $matches[0]; // WebP doesn't exist, return original
            }

            // Check if this image has been optimized with WebP enabled
            if (!$this->has_optimized_webp($filename)) {
                return $matches[0]; // Not optimized with WebP, return original
            }

            // Return WebP URL
            return 'src=' . $quote . $webp_url . $quote;
        }, $content);
    }

    /**
     * Check if an image has been optimized with WebP available
     *
     * @param string $filename The filename without extension.
     * @return bool
     */
    private function has_optimized_webp(string $filename): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'image_optimizer_history';

        // Find attachment ID by meta file path
        $attachment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'attachment'
				AND pm.meta_key = '_wp_attached_file'
				AND pm.meta_value LIKE %s
				LIMIT 1",
                '%' . $wpdb->esc_like($filename) . '%',
            ),
        );

        if (!$attachment) {
            return false;
        }

        // Check optimization history
        $history = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT webp_available FROM {$table} 
				WHERE attachment_id = %d 
				AND webp_available = 1 
				AND status = 'completed' 
				ORDER BY optimized_at DESC LIMIT 1",
                $attachment->ID,
            ),
        );

        return !empty($history);
    }

    /**
     * Check if browser supports WebP
     *
     * @return bool
     */
    private function browser_supports_webp(): bool
    {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }

        return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }
}
