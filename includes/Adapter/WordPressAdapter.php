<?php

declare(strict_types=1);

/**
 * WordPress Adapter Implementation
 *
 * Provides concrete implementation of WordPressAdapterInterface
 * by wrapping actual WordPress global functions.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Adapter;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Production WordPress adapter
 *
 * This implementation directly calls WordPress functions.
 * In tests, a mock implementation can be injected instead.
 */
class WordPressAdapter implements WordPressAdapterInterface
{
    /**
     * Check if current request is on a singular post/page
     *
     * @return bool True if singular, false otherwise.
     */
    public function is_singular(): bool
    {
        return is_singular();
    }

    /**
     * Get the featured image ID for current post
     *
     * @return int|false The attachment ID or false if none.
     */
    public function get_post_thumbnail_id()
    {
        return get_post_thumbnail_id();
    }

    /**
     * Get image URL for attachment ID and size
     *
     * @param int    $attachment_id The attachment ID.
     * @param string $size The image size.
     * @return string|false The image URL or false if not found.
     */
    public function get_attachment_image_url(int $attachment_id, string $size)
    {
        return wp_get_attachment_image_url($attachment_id, $size);
    }

    /**
     * Get responsive image srcset for attachment
     *
     * @param int    $attachment_id The attachment ID.
     * @param string $size The image size.
     * @return string|false The srcset string or false if not found.
     */
    public function get_attachment_image_srcset(int $attachment_id, string $size)
    {
        return wp_get_attachment_image_srcset($attachment_id, $size);
    }

    /**
     * Check if user is in admin area
     *
     * @return bool True if in admin, false otherwise.
     */
    public function is_admin(): bool
    {
        return is_admin();
    }

    /**
     * Dequeue a script by handle
     *
     * @param string $handle The script handle to dequeue.
     * @return bool True if dequeued, false if not registered.
     */
    public function dequeue_script(string $handle): bool
    {
        wp_dequeue_script($handle);
        return true;
    }

    /**
     * Remove WordPress action hook
     *
     * @param string   $hook The hook name.
     * @param callable $function_to_remove The callback to remove.
     * @param int      $priority The priority.
     * @return bool True if removed, false if not found.
     */
    public function remove_action(string $hook, callable $function_to_remove, int $priority = 10): bool
    {
        return remove_action($hook, $function_to_remove, $priority);
    }

    /**
     * Get WordPress option value
     *
     * @param string $option The option name.
     * @param mixed  $default The default value if not found.
     * @return mixed The option value or default.
     */
    public function get_option(string $option, $default = false)
    {
        return get_option($option, $default);
    }

    /**
     * Check if on a public-facing frontend request
     *
     * @return bool True if frontend, false if admin.
     */
    public function is_frontend(): bool
    {
        return ! is_admin();
    }
}
