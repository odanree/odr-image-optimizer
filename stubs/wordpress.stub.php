<?php
/**
 * WordPress Stubs for PHPStan Analysis
 * 
 * These are stub definitions for WordPress functions to help PHPStan
 * understand WordPress API calls without needing the full WordPress source.
 */

/**
 * Get option value from the options table
 *
 * @param string $option
 * @param mixed $default
 * @return mixed
 */
function get_option($option, $default = false) {}

/**
 * Update option value in the options table
 *
 * @param string $option
 * @param mixed $value
 * @return bool
 */
function update_option($option, $value) {}

/**
 * Add action hook
 *
 * @param string $hook
 * @param callable $function_to_add
 * @param int $priority
 * @param int $accepted_args
 * @return true
 */
function add_action($hook, $function_to_add, $priority = 10, $accepted_args = 1) {}

/**
 * Add filter hook
 *
 * @param string $hook
 * @param callable $function_to_add
 * @param int $priority
 * @param int $accepted_args
 * @return true
 */
function add_filter($hook, $function_to_add, $priority = 10, $accepted_args = 1) {}

/**
 * Remove action hook
 *
 * @param string $hook
 * @param callable $function_to_remove
 * @param int $priority
 * @return bool
 */
function remove_action($hook, $function_to_remove, $priority = 10) {}

/**
 * Remove filter hook
 *
 * @param string $hook
 * @param callable $function_to_remove
 * @param int $priority
 * @return bool
 */
function remove_filter($hook, $function_to_remove, $priority = 10) {}

/**
 * Apply filters
 *
 * @param string $hook
 * @param mixed $value
 * @return mixed
 */
function apply_filters($hook, $value) {}

/**
 * Do action
 *
 * @param string $hook
 * @return void
 */
function do_action($hook) {}

/**
 * Register activation hook
 *
 * @param string $file
 * @param callable $function
 * @return void
 */
function register_activation_hook($file, $function) {}

/**
 * Register deactivation hook
 *
 * @param string $file
 * @param callable $function
 * @return void
 */
function register_deactivation_hook($file, $function) {}

/**
 * Check if current request is for admin area
 *
 * @return bool
 */
function is_admin() {}

/**
 * Check if current request is for singular post/page
 *
 * @param string|string[] $post_types
 * @return bool
 */
function is_singular($post_types = '') {}

/**
 * Check if current request is for homepage
 *
 * @return bool
 */
function is_home() {}

/**
 * Check if current request is for front page
 *
 * @return bool
 */
function is_front_page() {}

/**
 * Check if current request is for archive page
 *
 * @return bool
 */
function is_archive() {}

/**
 * Get current post excerpt
 *
 * @param int|WP_Post|null $post
 * @return string
 */
function get_the_excerpt($post = null) {}

/**
 * Get blog information
 *
 * @param string $show
 * @return string
 */
function get_bloginfo($show = '') {}

/**
 * Get post thumbnail ID
 *
 * @param int|WP_Post|null $post
 * @return int
 */
function get_post_thumbnail_id($post = null) {}

/**
 * Escape HTML attribute
 *
 * @param string $text
 * @return string
 */
function esc_attr($text) {}

/**
 * Escape URL
 *
 * @param string $url
 * @param string[]|null $protocols
 * @return string
 */
function esc_url($url, $protocols = null) {}

/**
 * Escape HTML
 *
 * @param string $text
 * @return string
 */
function esc_html($text) {}

/**
 * Strip all HTML tags
 *
 * @param string $string
 * @param bool $remove_breaks
 * @return string
 */
function wp_strip_all_tags($string, $remove_breaks = false) {}

/**
 * Get the archive title
 *
 * @return string
 */
function get_the_archive_title() {}

/**
 * Get content URL
 *
 * @param string $path
 * @return string
 */
function content_url($path = '') {}

/**
 * Get WP_Styles global
 *
 * @return WP_Styles
 */
function wp_styles() {}

/**
 * Deregister script
 *
 * @param string $handle
 * @return void
 */
function wp_deregister_script($handle) {}

/**
 * Dequeue script
 *
 * @param string $handle
 * @return void
 */
function wp_dequeue_script($handle) {}

/**
 * Enqueue script
 *
 * @param string $handle
 * @param string $src
 * @param string[] $deps
 * @param string|bool $ver
 * @param bool $in_footer
 * @return void
 */
function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {}

/**
 * Enqueue style
 *
 * @param string $handle
 * @param string $src
 * @param string[] $deps
 * @param string|bool $ver
 * @param string $media
 * @return void
 */
function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {}

/**
 * Check if file exists
 *
 * @param string $path
 * @return bool
 */
function file_exists($path) {}

/**
 * Get file contents
 *
 * @param string $filename
 * @return string|false
 */
function file_get_contents($filename) {}

/**
 * Sanitize text field
 *
 * @param string $str
 * @return string
 */
function sanitize_text_field($str) {}

/**
 * Sanitize textarea field
 *
 * @param string $str
 * @return string
 */
function sanitize_textarea_field($str) {}

/**
 * Unslash value (remove escape slashes)
 *
 * @param mixed $value
 * @return mixed
 */
function wp_unslash($value) {}

/**
 * Add image size
 *
 * @param string $name
 * @param int $width
 * @param int $height
 * @param bool|string[] $crop
 * @return void
 */
function add_image_size($name, $width, $height, $crop = false) {}

/**
 * Get attachment metadata
 *
 * @param int $attachment_id
 * @param bool $unfiltered
 * @return array<string, mixed>|false
 */
function wp_get_attachment_metadata($attachment_id, $unfiltered = false) {}

/**
 * Get attachment image URL
 *
 * @param int $attachment_id
 * @param string|int[] $size
 * @param bool $icon
 * @return string|false
 */
function wp_get_attachment_image_url($attachment_id, $size = 'thumbnail', $icon = false) {}

/**
 * Get attachment image srcset
 *
 * @param int $attachment_id
 * @param string|int[] $size
 * @param WP_Post|null $post
 * @return string|false
 */
function wp_get_attachment_image_srcset($attachment_id, $size = 'medium', $post = null) {}

// WordPress Global Variables

class WP_Styles {
    /** @var array<string, mixed> */
    public $registered;
}

class WP_Post {
    /** @var int */
    public $ID;
    
    /** @var string */
    public $post_content;
    
    /** @var string */
    public $post_title;
}
