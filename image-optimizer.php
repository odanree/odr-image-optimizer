<?php
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
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'IMAGE_OPTIMIZER_VERSION', time() ); // Cache buster
define( 'IMAGE_OPTIMIZER_PATH', plugin_dir_path( __FILE__ ) );
define( 'IMAGE_OPTIMIZER_URL', plugin_dir_url( __FILE__ ) );
define( 'IMAGE_OPTIMIZER_BASENAME', plugin_basename( __FILE__ ) );

// Include the autoloader
require_once IMAGE_OPTIMIZER_PATH . 'includes/class-autoloader.php';

// Register the autoloader
\ImageOptimizer\Autoloader::register();

// Manually include core classes to ensure they're loaded
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-database.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-optimizer.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/core/class-api.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/admin/class-dashboard.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/admin/class-settings.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Frontend/WebpDelivery.php';
require_once IMAGE_OPTIMIZER_PATH . 'includes/Frontend/ResponsiveImages.php';

/**
 * The main plugin class
 */
use ImageOptimizer\Core;
use ImageOptimizer\Core\API;

/**
 * Initialize REST API early
 */
add_action( 'rest_api_init', function() {
	$api = new API();
	$api->register_routes();
}, 1 );

/**
 * Activate the plugin
 */
register_activation_hook( __FILE__, array( Core::class, 'activate' ) );

/**
 * Deactivate the plugin
 */
register_deactivation_hook( __FILE__, array( Core::class, 'deactivate' ) );

/**
 * Initialize WebP delivery early (plugins_loaded)
 */
add_action( 'plugins_loaded', function() {
	// Initialize WebP delivery FIRST - needs to hook early
	new \ImageOptimizer\Frontend\WebpDelivery();
	
	// Initialize responsive images
	new \ImageOptimizer\Frontend\ResponsiveImages();
}, 1 );

/**
 * Initialize the plugin
 */
add_action( 'init', function() {
	Core::get_instance();
}, 20 );

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
