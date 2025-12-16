<?php
/**
 * Admin Dashboard
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

use ImageOptimizer\Core\Database;

/**
 * Dashboard class
 */
class Dashboard {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_image-optimizer' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'image-optimizer-dashboard',
			IMAGE_OPTIMIZER_URL . 'assets/css/dashboard.css',
			array(),
			IMAGE_OPTIMIZER_VERSION
		);

		wp_enqueue_script(
			'image-optimizer-dashboard',
			IMAGE_OPTIMIZER_URL . 'assets/js/dashboard.js',
			array( 'wp-api-fetch', 'wp-element', 'wp-components' ),
			IMAGE_OPTIMIZER_VERSION,
			true
		);

		wp_localize_script(
			'image-optimizer-dashboard',
			'imageOptimizerData',
			array(
				'nonce' => wp_create_nonce( 'image_optimizer_nonce' ),
				'rest_url' => rest_url( 'image-optimizer/v1/' ),
			)
		);
	}

	/**
	 * Render dashboard
	 */
	public static function render() {
		?>
		<div class="wrap image-optimizer-wrap">
			<h1><?php esc_html_e( 'Image Optimizer', 'image-optimizer' ); ?></h1>
			<div id="image-optimizer-dashboard"></div>
		</div>
		<?php
	}
}
