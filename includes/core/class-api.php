<?php
/**
 * REST API endpoints for Image Optimizer
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

use ImageOptimizer\Core\Database;

/**
 * REST API class
 */
class API {

	const NAMESPACE = 'image-optimizer/v1';
	const NONCE_FIELD = 'image_optimizer_nonce';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Routes are registered directly from the plugin file
	}

	/**
	 * Register REST routes
	 */
	public function register_routes() {
		// Test endpoint (public)
		register_rest_route(
			self::NAMESPACE,
			'/test',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'test_endpoint' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get optimization statistics
		register_rest_route(
			self::NAMESPACE,
			'/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_statistics' ),
				'permission_callback' => '__return_true',
			)
		);

		// Get image optimization history
		register_rest_route(
			self::NAMESPACE,
			'/history/(?P<attachment_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_history' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'attachment_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);

		// Bulk get images
		register_rest_route(
			self::NAMESPACE,
			'/images',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_images' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'paged'  => array(
						'type'    => 'integer',
						'default' => 1,
					),
					'status' => array(
						'type' => 'string',
					),
				),
			)
		);

		// Optimize single image
		register_rest_route(
			self::NAMESPACE,
			'/optimize/(?P<attachment_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'optimize_image' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'attachment_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);

		// Revert optimization
		register_rest_route(
			self::NAMESPACE,
			'/revert/(?P<attachment_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'revert_image' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'attachment_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Check admin permission
	 *
	 * @return bool
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_statistics( $request ) {
		$stats = Database::get_statistics();

		return rest_ensure_response(
			array(
				'total_optimized'     => (int) $stats->total_optimized,
				'total_original_size' => (int) $stats->total_original_size,
				'total_optimized_size' => (int) $stats->total_optimized_size,
				'total_savings'       => (int) ( $stats->total_original_size - $stats->total_optimized_size ),
				'average_compression' => (float) $stats->average_compression,
				'webp_count'          => (int) $stats->webp_count,
			)
		);
	}

	/**
	 * Test endpoint
	 *
	 * @return \WP_REST_Response
	 */
	public function test_endpoint() {
		return rest_ensure_response(
			array(
				'status' => 'API is working!',
				'timestamp' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Get optimization history for an image
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_history( $request ) {
		$attachment_id = $request['attachment_id'];
		$history = Database::get_optimization_history( $attachment_id );

		if ( ! $history ) {
			return rest_ensure_response(
				array(
					'error' => 'No optimization history found',
				),
				404
			);
		}

		return rest_ensure_response( $this->format_history( $history ) );
	}

	/**
	 * Get paginated list of images
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_images( $request ) {
		$paged = $request->get_param( 'paged' ) ?? 1;
		$per_page = 20;

		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ),
			'post_status'    => 'inherit',
			'paged'          => $paged,
			'posts_per_page' => $per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new \WP_Query( $args );
		$images = array();

		foreach ( $query->posts as $post ) {
			$attached_file = get_attached_file( $post->ID );
			$file_size = $attached_file ? filesize( $attached_file ) : 0;
			$history = Database::get_optimization_history( $post->ID );
			
			$images[] = array(
				'id'              => $post->ID,
				'title'           => $post->post_title,
				'filename'        => basename( $attached_file ? $attached_file : '' ),
				'url'             => wp_get_attachment_url( $post->ID ),
				'size'            => $file_size,
				'optimized'       => ! empty( $history ),
				'optimization'    => $history ? $this->format_history( $history ) : null,
			);
		}

		return rest_ensure_response(
			array(
				'images' => $images,
				'paged'  => $paged,
				'total'  => $query->found_posts,
				'pages'  => $query->max_num_pages,
			)
		);
	}

	/**
	 * Optimize a single image
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function optimize_image( $request ) {
		$attachment_id = $request['attachment_id'];

		if ( ! get_post( $attachment_id ) ) {
			return new \WP_Error( 'invalid_attachment', 'Invalid attachment ID', array( 'status' => 404 ) );
		}

		$optimizer = new Optimizer();
		$result = $optimizer->optimize_attachment( $attachment_id );

		if ( $result['success'] ) {
			return rest_ensure_response( $result );
		}

		return new \WP_Error( 'optimization_failed', $result['error'], array( 'status' => 400 ) );
	}

	/**
	 * Revert image optimization
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function revert_image( $request ) {
		$attachment_id = $request['attachment_id'];

		if ( ! get_post( $attachment_id ) ) {
			return new \WP_Error( 'invalid_attachment', 'Invalid attachment ID', array( 'status' => 404 ) );
		}

		$optimizer = new Optimizer();
		$result = $optimizer->revert_optimization( $attachment_id );

		if ( $result['success'] ) {
			return rest_ensure_response( $result );
		}

		return new \WP_Error( 'revert_failed', $result['error'], array( 'status' => 400 ) );
	}

	/**
	 * Format history for API response
	 *
	 * @param object $history The history object.
	 * @return array
	 */
	private function format_history( $history ) {
		return array(
			'id'                => (int) $history->id,
			'attachment_id'     => (int) $history->attachment_id,
			'original_size'     => (int) $history->original_size,
			'optimized_size'    => (int) $history->optimized_size,
			'savings'           => (int) ( $history->original_size - $history->optimized_size ),
			'compression_ratio' => (float) $history->compression_ratio,
			'method'            => $history->optimization_method,
			'webp_available'    => (bool) $history->webp_available,
			'status'            => $history->status,
			'optimized_at'      => $history->optimized_at,
		);
	}
}
