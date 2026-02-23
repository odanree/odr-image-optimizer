<?php

declare(strict_types=1);

/**
 * WordPress Adapter Interface
 *
 * Abstracts WordPress global functions to enable testability and loose coupling.
 * Implementations wrap WordPress functions (is_singular, get_post_thumbnail_id, etc.)
 * so services depend on this interface instead of direct WordPress function calls.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Adapter;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Contract for WordPress environment access
 *
 * Implementing this interface allows services to interact with WordPress
 * without direct function calls, enabling:
 * - Unit testing (mock implementation)
 * - Dependency inversion (depends on interface, not WordPress functions)
 * - Isolation (services don't depend on WordPress bootstrap)
 */
interface WordPressAdapterInterface
{
    /**
     * Check if current request is on a singular post/page
     *
     * Wraps: is_singular()
     *
     * @return bool True if singular, false otherwise.
     */
    public function is_singular(): bool;

    /**
     * Get the featured image ID for current post
     *
     * Wraps: get_post_thumbnail_id()
     *
     * @return int|false The attachment ID or false if none.
     */
    public function get_post_thumbnail_id();

    /**
     * Get image URL for attachment ID and size
     *
     * Wraps: wp_get_attachment_image_url()
     *
     * @param int    $attachment_id The attachment ID.
     * @param string $size The image size (e.g., 'full', 'large', 'odr_content_optimized').
     * @return string|false The image URL or false if not found.
     */
    public function get_attachment_image_url(int $attachment_id, string $size);

    /**
     * Get responsive image srcset for attachment
     *
     * Wraps: wp_get_attachment_image_srcset()
     *
     * @param int    $attachment_id The attachment ID.
     * @param string $size The image size.
     * @return string|false The srcset string or false if not found.
     */
    public function get_attachment_image_srcset(int $attachment_id, string $size);

    /**
     * Check if user is in admin area
     *
     * Wraps: is_admin()
     *
     * @return bool True if in admin, false otherwise.
     */
    public function is_admin(): bool;

    /**
     * Dequeue a script by handle
     *
     * Wraps: wp_dequeue_script()
     *
     * @param string $handle The script handle to dequeue.
     * @return bool True if dequeued, false if not registered.
     */
    public function dequeue_script(string $handle): bool;

    /**
     * Remove WordPress action hook
     *
     * Wraps: remove_action()
     *
     * @param string   $hook The hook name.
     * @param callable $function_to_remove The callback to remove.
     * @param int      $priority The priority.
     * @return bool True if removed, false if not found.
     */
    public function remove_action(string $hook, callable $function_to_remove, int $priority = 10): bool;

    /**
     * Get WordPress option value
     *
     * Wraps: get_option()
     *
     * @param string $option The option name.
     * @param mixed  $default The default value if not found.
     * @return mixed The option value or default.
     */
    public function get_option(string $option, $default = false);

    /**
     * Check if on a public-facing frontend request
     *
     * Wraps: is_admin() negation
     *
     * @return bool True if frontend, false if admin.
     */
    public function is_frontend(): bool;
}
