<?php

declare(strict_types=1);
/**
 * Image Optimizer Plugin
 *
 * @package           ImageOptimizer
 * @author            Danh Le
 * @license           GPL v2 or later
 * @link              https://danhle.net
 *
 * @wordpress-plugin
 * Plugin Name:       ODR Image Optimizer
 * Plugin URI:        https://wordpress.org/plugins/odr-image-optimizer/
 * Description:       Professional image optimization with intelligent compression, WebP conversion, and lazy loading.
 * Version:           1.0.0
 * Author:            Danh Le
 * Author URI:        https://danhle.net
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       odr-image-optimizer
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Tested up to:      6.9
 * Stable tag:        1.0.0
 * Tags:              image optimization, performance, wordpress plugin, caching, compression
 */

// If this file is called directly, abort.
if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Define plugin constants
define('IMAGE_OPTIMIZER_VERSION', time()); // Cache buster
define('IMAGE_OPTIMIZER_PATH', plugin_dir_path(__FILE__));
define('IMAGE_OPTIMIZER_URL', plugin_dir_url(__FILE__));
define('IMAGE_OPTIMIZER_BASENAME', plugin_basename(__FILE__));

// Include the autoloader
require_once IMAGE_OPTIMIZER_PATH . 'includes/class-autoloader.php';

// Register the autoloader
\ImageOptimizer\Autoloader::register();

// Manually include core classes to ensure they're loaded
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/interface-optimizer.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-result.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-optimizer-config.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-resizing-config.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-image-file.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-image-context.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-tool-registry.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-container.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-permissions-manager.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-database.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-media-transformer.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-image-resizer.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-resizing-processor.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-optimizer.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-optimizer-contract-validator.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-dip-audit.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-hook-contract-validator.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-isolation-audit.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-permission-enforcement-audit.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-hook-complexity-analyzer.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-api.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Services/class-size-selector.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Services/class-size-registry.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Services/class-layout-policy.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Services/class-header-manager.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Services/class-asset-manager.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Services/class-priority-service.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Services/class-cleanup-service.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/frontend/class-responsive-image-service.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Frontend/class-frontend-delivery.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/frontend/class-webp-frontend-delivery.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/admin/class-dashboard.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/admin/class-settings.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Frontend/WebpDelivery.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Frontend/ResponsiveImages.php';

/**
 * The main plugin class
 */
use ImageOptimizer\Core;
use ImageOptimizer\Core\API;
use ImageOptimizer\Services\SizeRegistry;

/**
 * Initialize custom image size registry early
 *
 * Register custom image sizes (odr_mobile_optimized, odr_tablet_optimized)
 * that bridge the gap between mobile and desktop breakpoints.
 */
add_action(
    'after_setup_theme',
    function () {
        $registry = new SizeRegistry();
        $registry->register_optimized_sizes();

        // Hook custom sizes into srcset calculation
        add_filter('wp_calculate_image_srcset_meta', [ $registry, 'add_to_srcset' ]);
    },
);

/**
 * Initialize REST API early
 */
add_action('rest_api_init', function () {
    $api = new API();
    $api->register_routes();
}, 1);

/**
 * Activate the plugin
 */
register_activation_hook(__FILE__, [ Core::class, 'activate' ]);

/**
 * Deactivate the plugin
 */
register_deactivation_hook(__FILE__, [ Core::class, 'deactivate' ]);

/**
 * Initialize WebP delivery early (plugins_loaded)
 */
add_action('plugins_loaded', function () {
    // Initialize WebP delivery FIRST - needs to hook early
    new \ImageOptimizer\Frontend\WebpDelivery();

    // Initialize responsive images
    new \ImageOptimizer\Frontend\ResponsiveImages();
}, 1);

/**
 * Initialize the plugin
 */
add_action('init', function () {
    Core::get_instance();
}, 20);

/**
 * Initialize performance optimizations (before content renders)
 */
add_action('template_redirect', function () {
    if (! is_admin()) {
        // Detect LCP image ID early (before wp_head)
        $priority_service = new \ImageOptimizer\Services\PriorityService();
        $priority_service->detect_lcp_id();

        // Apply cache headers for long-term caching
        $header_manager = new \ImageOptimizer\Services\HeaderManager();
        $header_manager->apply_cache_headers();

        // Optimize critical rendering path
        $asset_manager = new \ImageOptimizer\Services\AssetManager();
        $asset_manager->optimize_critical_path();
    }
}, 1);

/**
 * Initialize frontend styles and fonts in wp_head (very early)
 */
add_action('wp_head', function () {
    if (! is_admin()) {
        $priority_service = new \ImageOptimizer\Services\PriorityService();

        // Inject LCP preload hint (tell browser to download 704px image immediately)
        $priority_service->inject_preload();

        // Preload theme's primary font to reduce FCP variance
        $priority_service->preload_theme_font();

        // Inline small CSS to eliminate render-blocking request
        $asset_manager = new \ImageOptimizer\Services\AssetManager();
        $asset_manager->inline_frontend_styles();

        // Preload critical fonts (breaks dependency chain)
        $asset_manager->preload_critical_fonts();
    }
}, 1);

// Initialize frontend WebP delivery for public-facing posts
add_action('wp', function () {
    if (! is_admin()) {
        \ImageOptimizer\Frontend\WebPFrontendDelivery::init();

        // Create the delivery service for filter handling + connection hints
        $delivery = new \ImageOptimizer\Frontend\FrontendDelivery();

        // Add the strictly-typed Frontend_Delivery filter at priority 20
        // This ensures we run after theme defaults but before other plugins
        add_filter('wp_get_attachment_image_attributes', [ $delivery, 'serve_optimized_attributes' ], 20, 2);

        // Add preconnect hints in wp_head (priority 1 = very early)
        // Warms up DNS/SSL for media domain before browser reaches image tag
        add_action('wp_head', [ $delivery, 'add_connection_hints' ], 1);
    }
});

/**
 * Remove WordPress bloat (priority 100 = very late, after all plugins enqueue)
 */
add_action('wp_enqueue_scripts', function () {
    if (! is_admin()) {
        $cleanup = new \ImageOptimizer\Services\CleanupService();
        $cleanup->remove_bloat();
    }
}, 100);

/**
 * Copyright (C) 2025 Danh Le
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1335 USA.
 */
