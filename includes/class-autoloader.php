<?php
/**
 * Autoloader for Image Optimizer classes
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer;

/**
 * Autoloader class
 */
class Autoloader {

	/**
	 * Register the autoloader
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload function
	 *
	 * @param string $class The class name.
	 */
	public static function autoload( $class ) {
		// Only autoload ImageOptimizer classes
		if ( 0 !== strpos( $class, 'ImageOptimizer' ) ) {
			return;
		}

		// Remove the namespace prefix
		$class_name = str_replace( 'ImageOptimizer\\', '', $class );

		// Convert namespace to file path
		$file = IMAGE_OPTIMIZER_PATH . 'includes/class-' . strtolower( str_replace( '\\', '/', $class_name ) ) . '.php';

		// Load the file if it exists
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
