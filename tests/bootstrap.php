<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 *
 * Sets up the test environment for ImageOptimizer tests
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define WordPress constants for plugin tests
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// Define plugin constants
if (!defined('IMAGE_OPTIMIZER_PATH')) {
    define('IMAGE_OPTIMIZER_PATH', dirname(__DIR__) . '/');
}
if (!defined('IMAGE_OPTIMIZER_URL')) {
    define('IMAGE_OPTIMIZER_URL', 'http://localhost/wp-content/plugins/odr-image-optimizer/');
}
if (!defined('IMAGE_OPTIMIZER_VERSION')) {
    define('IMAGE_OPTIMIZER_VERSION', '2.0.0-refactor');
}

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// ImageOptimizer autoloader
require_once dirname(__DIR__) . '/includes/class-autoloader.php';

\ImageOptimizer\Autoloader::register();

// Explicitly load admin classes that are needed by Services but excluded from autoloader
// These are in includes/admin/ which is excluded from phpunit.xml source analysis
require_once dirname(__DIR__) . '/includes/admin/class-settings-policy.php';

// WordPress function stubs for testing
// Global store for options
global $wp_options;
$wp_options = [];

// Global store for actions/hooks
global $wp_actions;
$wp_actions = [];

// Global store for enqueued scripts
global $wp_scripts;
$wp_scripts = [];

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $wp_options;
        $wp_options[$option] = $value;
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $wp_options;
        return $wp_options[$option] ?? $default;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $args = []) {
        global $wp_scripts;
        $wp_scripts[$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver];
        return true;
    }
}

if (!function_exists('wp_script_is')) {
    function wp_script_is($handle, $status = 'registered') {
        global $wp_scripts;
        if ($status === 'enqueued') {
            return isset($wp_scripts[$handle]);
        }
        return isset($wp_scripts[$handle]);
    }
}

if (!function_exists('wp_dequeue_script')) {
    function wp_dequeue_script($handle) {
        global $wp_scripts;
        unset($wp_scripts[$handle]);
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
        global $wp_actions;
        if (!isset($wp_actions[$hook])) {
            $wp_actions[$hook] = [];
        }
        $wp_actions[$hook][$priority][] = ['func' => $function_to_add, 'args' => $accepted_args];
        return true;
    }
}

if (!function_exists('remove_action')) {
    function remove_action($hook, $function_to_remove, $priority = 10) {
        global $wp_actions;
        if (isset($wp_actions[$hook][$priority])) {
            foreach ($wp_actions[$hook][$priority] as $key => $action) {
                if ($action['func'] === $function_to_remove) {
                    unset($wp_actions[$hook][$priority][$key]);
                }
            }
        }
        return true;
    }
}

if (!function_exists('has_action')) {
    function has_action($hook, $function_to_check = false) {
        global $wp_actions;
        if (!isset($wp_actions[$hook])) {
            return false;
        }
        if ($function_to_check === false) {
            return !empty($wp_actions[$hook]);
        }
        foreach ($wp_actions[$hook] as $priority => $actions) {
            foreach ($actions as $action) {
                if ($action['func'] === $function_to_check) {
                    return $priority;
                }
            }
        }
        return false;
    }
}

if (!function_exists('is_singular')) {
    function is_singular($post_types = '') {
        // For testing, assume we're on a single post page
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        // For testing, assume we're on the frontend
        return false;
    }
}

if (!function_exists('get_post_thumbnail_id')) {
    function get_post_thumbnail_id($post = null) {
        // For testing, return a mock thumbnail ID
        return 1;
    }
}

if (!function_exists('wp_get_attachment_image')) {
    function wp_get_attachment_image($attachment_id, $size = 'thumbnail', $icon = false, $attr = '') {
        // For testing, return a mock image tag
        return '<img src="http://example.com/image.jpg" />';
    }
}

if (!function_exists('get_the_post_thumbnail')) {
    function get_the_post_thumbnail($post = null, $size = 'post-thumbnail', $attr = '') {
        return wp_get_attachment_image(get_post_thumbnail_id($post), $size, false, $attr);
    }
}

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax() {
        return false;
    }
}

if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url($attachment_id, $size = 'thumbnail', $icon = false) {
        // For testing, return a mock image URL
        return 'http://example.com/image-' . $attachment_id . '.jpg';
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $context = 'display') {
        return $url;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_get_attachment_image_srcset')) {
    function wp_get_attachment_image_srcset($attachment_id, $size = 'medium', $image_meta = null) {
        // For testing, return a mock srcset
        return 'http://example.com/image-300.jpg 300w, http://example.com/image-600.jpg 600w';
    }
}

if (!function_exists('wp_get_attachment_image_sizes')) {
    function wp_get_attachment_image_sizes($attachment_id, $size = 'medium', $image_meta = null) {
        // For testing, return a mock sizes attribute
        return '(max-width: 600px) 100vw, 600px';
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($string) {
        return $string;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($string) {
        return trim(strip_tags($string));
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
        global $wp_filters;
        if (!isset($wp_filters)) {
            $wp_filters = [];
        }
        if (!isset($wp_filters[$hook])) {
            $wp_filters[$hook] = [];
        }
        if (!isset($wp_filters[$hook][$priority])) {
            $wp_filters[$hook][$priority] = [];
        }
        $wp_filters[$hook][$priority][] = ['func' => $function_to_add, 'args' => $accepted_args];
        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook, $function_to_remove, $priority = 10) {
        global $wp_filters;
        if (isset($wp_filters[$hook][$priority])) {
            foreach ($wp_filters[$hook][$priority] as $key => $filter) {
                if ($filter['func'] === $function_to_remove) {
                    unset($wp_filters[$hook][$priority][$key]);
                }
            }
        }
        return true;
    }
}

if (!function_exists('has_filter')) {
    function has_filter($hook, $function_to_check = false) {
        global $wp_filters;
        if (!isset($wp_filters[$hook])) {
            return false;
        }
        if ($function_to_check === false) {
            return !empty($wp_filters[$hook]);
        }
        foreach ($wp_filters[$hook] as $priority => $filters) {
            foreach ($filters as $filter) {
                if ($filter['func'] === $function_to_check) {
                    return $priority;
                }
            }
        }
        return false;
    }
}

if (!function_exists('has_action')) {
    function has_action($hook, $function_to_check = false) {
        return \has_filter($hook, $function_to_check);
    }
}

if (!function_exists('wp_strip_post_tags')) {
    function wp_strip_post_tags($post, $remove_blocks = false) {
        if (is_object($post)) {
            $post = $post->post_content;
        }
        return strip_tags($post);
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = null) {
        if (null === $more) {
            $more = __('&hellip;');
        }
        $original_text = $text;
        $text = wp_strip_all_tags($text);
        $words_array = preg_split("/[\s]+/u", $text, ($num_words + 1), PREG_SPLIT_NO_EMPTY);
        if (count($words_array) > $num_words) {
            array_pop($words_array);
            $text = implode(' ', $words_array);
            $text = $text . $more;
        } else {
            $text = implode(' ', $words_array);
        }
        return $text;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($text, $remove_breaks = false) {
        if (is_null($text)) {
            return '';
        }
        $text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text);
        $text = strip_tags($text);
        if ($remove_breaks) {
            $text = preg_replace('/[\r\n\t ]+/', ' ', $text);
        }
        return trim($text);
    }
}

if (!function_exists('is_home')) {
    function is_home() {
        return false;
    }
}

if (!function_exists('is_singular')) {
    function is_singular($post_types = '') {
        return false;
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        if ('description' === $show) {
            return 'Test Site Description';
        }
        return 'Test Site';
    }
}

// WordPress class stubs for type checking
if (!class_exists('WP_Styles')) {
    class WP_Styles {
        /**
         * Registered styles
         *
         * @var array<string, object>
         */
        public array $registered = [];

        /**
         * Enqueued styles queue
         *
         * @var array<int, string>
         */
        public array $queue = [];

        /**
         * Constructor
         */
        public function __construct() {
            $this->registered = [];
            $this->queue = [];
        }
    }
}

if (!function_exists('wp_styles')) {
    function wp_styles() {
        return new WP_Styles();
    }
}
