<?php

declare(strict_types=1);

/**
 * Admin Dashboard
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Dashboard class
 */
class Dashboard
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ]);
    }

    /**
     * Enqueue scripts and styles
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts($hook)
    {
        // Hook name for submenu page under tools.php: tools_page_{page_slug}
        if ('tools_page_image-optimizer' !== $hook) {
            return;
        }

        // Use current time as cache buster with microtime for maximum uniqueness
        $cache_buster = str_replace('.', '', (string) microtime(true));

        wp_enqueue_style(
            'image-optimizer-dashboard',
            IMAGE_OPTIMIZER_URL . 'assets/css/dashboard.css?t=' . $cache_buster,
            [],
            false,
        );

        wp_enqueue_script(
            'image-optimizer-dashboard',
            IMAGE_OPTIMIZER_URL . 'assets/js/dashboard.js?t=' . $cache_buster,
            [],
            false,
            true,
        );

        wp_localize_script(
            'image-optimizer-dashboard',
            'imageOptimizerData',
            [
                'nonce' => wp_create_nonce('wp_rest'),
                'rest_url' => rest_url('image-optimizer/v1/'),
            ],
        );
    }

    /**
     * Render dashboard
     */
    public static function render()
    {
        ?>
		<div class="wrap image-optimizer-wrap">
			<h1><?php esc_html_e('ODR Image Optimizer', 'odr-image-optimizer'); ?></h1>
			<div id="image-optimizer-dashboard"></div>
		</div>
		<?php
    }
}
