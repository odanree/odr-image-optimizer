<?php

declare(strict_types=1);

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
		// Hook into image URL generation (attachments)
		add_filter('wp_get_attachment_url', [ $this, 'maybe_serve_webp' ], 10, 2);
		
		// Hook into image src array (used by wp_get_attachment_image_src)
		add_filter('wp_get_attachment_image_src', [ $this, 'maybe_serve_webp_src' ], 10, 3);
		
		// Hook into content to replace image URLs globally
		add_filter('the_content', [ $this, 'maybe_serve_webp_in_content' ], 999 );

		// Check if WebP delivery is enabled in settings
		$settings = get_option('image_optimizer_settings', []);
		$this->enabled = ! empty($settings['enable_webp_delivery']);
	}

	/**
	 * Maybe serve WebP version of an attachment
	 *
	 * @param string $url The attachment URL.
	 * @param int    $attachment_id The attachment ID.
	 * @return string
	 */
	public function maybe_serve_webp(string $url, int $attachment_id): string
	{
		if (!$this->enabled || !$this->browser_supports_webp()) {
			return $url;
		}

		// Get the file path
		$file = get_attached_file($attachment_id);
		if (!$file) {
			return $url;
		}

		$webp_path = $file . '.webp';

		// Check if WebP exists on disk
		if (!file_exists($webp_path)) {
			return $url;
		}

		// Check if this image has been optimized with WebP enabled
		global $wpdb;
		$table = $wpdb->prefix . 'image_optimizer_history';
		
		$history = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT webp_available FROM {$table} WHERE attachment_id = %d AND status = 'completed' ORDER BY optimized_at DESC LIMIT 1",
				$attachment_id
			)
		);

		if (!$history || !$history->webp_available) {
			return $url;
		}

		// Return WebP URL instead of original
		return $url . '.webp';
	}

	/**
	 * Maybe serve WebP in image src array
	 *
	 * @param array|false $image The image array.
	 * @param int         $attachment_id The attachment ID.
	 * @param string|array $size The image size.
	 * @return array|false
	 */
	public function maybe_serve_webp_src($image, int $attachment_id, $size)
	{
		if (!is_array($image) || !$this->enabled || !$this->browser_supports_webp()) {
			return $image;
		}

		// Get the file path
		$file = get_attached_file($attachment_id);
		if (!$file) {
			return $image;
		}

		$webp_path = $file . '.webp';

		// Check if WebP exists and is recorded
		if (!file_exists($webp_path)) {
			return $image;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'image_optimizer_history';
		
		$history = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT webp_available FROM {$table} WHERE attachment_id = %d AND status = 'completed' ORDER BY optimized_at DESC LIMIT 1",
				$attachment_id
			)
		);

		if (!$history || !$history->webp_available) {
			return $image;
		}

		// Replace the URL in the array
		if (isset($image[0]) && (strpos($image[0], '.jpg') !== false || strpos($image[0], '.png') !== false)) {
			$image[0] = $image[0] . '.webp';
		}

		return $image;
	}

	/**
	 * Replace image URLs in content with WebP versions
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public function maybe_serve_webp_in_content(string $content): string
	{
		if (!$this->enabled || !$this->browser_supports_webp()) {
			return $content;
		}

		// Pattern to find image src attributes pointing to jpg/png files
		$pattern = '/src=["\']([^"\']*\.(jpg|jpeg|png))(["\'])/i';

		return preg_replace_callback($pattern, function($matches) {
			$url = $matches[1];
			$extension = $matches[2];
			$quote = $matches[3];

			// Check if corresponding WebP file exists
			$upload_dir = wp_get_upload_dir();
			$relative_path = str_replace($upload_dir['baseurl'], '', $url);
			$full_path = $upload_dir['basedir'] . $relative_path;
			$webp_path = $full_path . '.webp';

			if (file_exists($webp_path)) {
				// Replace with WebP
				return 'src=' . $quote . $url . '.webp' . $quote;
			}

			return $matches[0];
		}, $content);
	}

	/**
	 * Check if browser supports WebP
	 *
	 * Modern browsers (2024+) all support WebP, but we check the Accept header for safety.
	 * Most production setups can safely always return true.
	 *
	 * @return bool
	 */
	private function browser_supports_webp(): bool
	{
		// Check Accept header for image/webp (most reliable method)
		$accept_header = isset($_SERVER['HTTP_ACCEPT']) ? sanitize_text_field($_SERVER['HTTP_ACCEPT']) : '';

		if (strpos($accept_header, 'image/webp') !== false) {
			return true;
		}

		// Fallback: Check if we can safely assume WebP support
		// This is optional but recommended for modern setups
		// Uncomment if you want to always serve WebP (browser will handle format):
		// return true;

		return false;
	}

	/**
	 * Enable WebP delivery
	 */
	public function enable(): void
	{
		$this->enabled = true;
	}

	/**
	 * Disable WebP delivery
	 */
	public function disable(): void
	{
		$this->enabled = false;
	}

	/**
	 * Check if WebP delivery is enabled
	 *
	 * @return bool
	 */
	public function is_enabled(): bool
	{
		return $this->enabled;
	}
}
