<?php
/**
 * Image optimization engine
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

use ImageOptimizer\Core\Database;

/**
 * Main image optimizer class
 */
class Optimizer {

	/**
	 * Compression levels
	 */
	const COMPRESSION_LOW = 1;
	const COMPRESSION_MEDIUM = 2;
	const COMPRESSION_HIGH = 3;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		// Optimize image on upload
		add_filter( 'wp_handle_upload', array( $this, 'optimize_on_upload' ) );

		// Optimize existing images via admin
		add_action( 'admin_action_image_optimizer_optimize', array( $this, 'optimize_single_image' ) );

		// Bulk optimization via AJAX
		add_action( 'wp_ajax_image_optimizer_bulk_optimize', array( $this, 'ajax_bulk_optimize' ) );
	}

	/**
	 * Optimize image on upload
	 *
	 * @param array $upload The upload data.
	 * @return array
	 */
	public function optimize_on_upload( $upload ) {
		$settings = get_option( 'image_optimizer_settings', array() );

		if ( empty( $settings['auto_optimize'] ) ) {
			return $upload;
		}

		if ( isset( $upload['file'] ) && $this->is_optimizable( $upload['file'] ) ) {
			$this->optimize_file( $upload['file'] );
		}

		return $upload;
	}

	/**
	 * Optimize a single image
	 */
	public function optimize_single_image() {
		check_admin_referer( 'image_optimizer_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		$attachment_id = isset( $_GET['attachment_id'] ) ? absint( $_GET['attachment_id'] ) : 0;

		if ( ! $attachment_id ) {
			wp_die( 'Invalid attachment ID', 400 );
		}

		$result = $this->optimize_attachment( $attachment_id );

		wp_send_json( $result );
	}

	/**
	 * AJAX bulk optimization
	 */
	public function ajax_bulk_optimize() {
		check_ajax_referer( 'image_optimizer_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'absint', $_POST['attachment_ids'] ) : array();

		if ( empty( $attachment_ids ) ) {
			wp_send_json_error( 'No attachments provided' );
		}

		$results = array();
		foreach ( $attachment_ids as $attachment_id ) {
			$results[ $attachment_id ] = $this->optimize_attachment( $attachment_id );
		}

		wp_send_json_success( $results );
	}

	/**
	 * Optimize an attachment
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return array
	 */
	public function optimize_attachment( $attachment_id ) {
		try {
			$file = get_attached_file( $attachment_id );

			if ( ! $file || ! file_exists( $file ) ) {
				return array(
					'success' => false,
					'error'   => 'File not found',
				);
			}

			if ( ! $this->is_optimizable( $file ) ) {
				return array(
					'success' => false,
					'error'   => 'File type not supported',
				);
			}

			$original_size = filesize( $file );

			// Create backup before optimization
			$backup_file = $this->create_backup( $file, $attachment_id );

			// Get optimization method based on file type
			$method = $this->get_optimization_method( $file );

			// Perform optimization
			$result = $this->optimize_file( $file, $method );

			if ( ! $result ) {
				return array(
					'success' => false,
					'error'   => 'Optimization failed',
				);
			}

			$optimized_size = filesize( $file );
			$savings = $original_size - $optimized_size;
			$compression_ratio = $savings > 0 ? ( $savings / $original_size ) * 100 : 0;

			// Check if WebP version can be created
			$webp_available = $this->can_create_webp( $file );
			if ( $webp_available ) {
				$this->create_webp_version( $file );
			}

			// Save optimization result
			Database::save_optimization_result(
				$attachment_id,
				array(
					'original_size'  => $original_size,
					'optimized_size' => $optimized_size,
					'compression_ratio' => $compression_ratio,
					'method'         => $method,
					'webp_available' => $webp_available,
					'status'         => 'completed',
					'backup_file'    => $backup_file,
				)
			);

			return array(
				'success'            => true,
				'original_size'      => $original_size,
				'optimized_size'     => $optimized_size,
				'savings'            => $savings,
				'compression_ratio'  => $compression_ratio,
				'webp_available'     => $webp_available,
			);
		} catch ( \Exception $e ) {
			Database::save_optimization_result(
				$attachment_id,
				array(
					'original_size'  => 0,
					'optimized_size' => 0,
					'compression_ratio' => 0,
					'method'         => 'error',
					'webp_available' => false,
					'status'         => 'failed',
					'error'          => $e->getMessage(),
				)
			);

			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Optimize a file
	 *
	 * @param string $file_path The file path.
	 * @param string $method The optimization method.
	 * @return bool
	 */
	private function optimize_file( $file_path, $method = 'standard' ) {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$file_type = wp_check_filetype( $file_path );
		$mime_type = $file_type['type'];

		switch ( $mime_type ) {
			case 'image/jpeg':
				return $this->optimize_jpeg( $file_path );
			case 'image/png':
				return $this->optimize_png( $file_path );
			case 'image/webp':
				return $this->optimize_webp( $file_path );
			case 'image/gif':
				return $this->optimize_gif( $file_path );
			default:
				return false;
		}
	}

	/**
	 * Optimize JPEG image
	 *
	 * @param string $file_path The file path.
	 * @return bool
	 */
	private function optimize_jpeg( $file_path ) {
		// Using GD library as fallback, in production you'd use external services like TinyPNG, ImageOptim, or ShortPixel
		$settings = get_option( 'image_optimizer_settings', array() );
		$compression = $settings['compression_level'] ?? 'medium';

		$quality = $this->get_quality_level( $compression );

		try {
			$image = imagecreatefromjpeg( $file_path );
			if ( ! $image ) {
				return false;
			}

			// Reduce image colors and quality
			imagetruecolortopalette( $image, false, 256 );
			$result = imagejpeg( $image, $file_path, $quality );
			imagedestroy( $image );

			return $result;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Optimize PNG image
	 *
	 * @param string $file_path The file path.
	 * @return bool
	 */
	private function optimize_png( $file_path ) {
		$settings = get_option( 'image_optimizer_settings', array() );
		$compression = $settings['compression_level'] ?? 'medium';

		$compression_level = $this->get_compression_level( $compression );

		try {
			$image = imagecreatefrompng( $file_path );
			if ( ! $image ) {
				return false;
			}

			// Reduce colors for better compression
			imagetruecolortopalette( $image, false, 256 );
			$result = imagepng( $image, $file_path, $compression_level );
			imagedestroy( $image );

			return $result;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Optimize WebP image
	 *
	 * @param string $file_path The file path.
	 * @return bool
	 */
	private function optimize_webp( $file_path ) {
		try {
			$image = imagecreatefromwebp( $file_path );
			if ( ! $image ) {
				return false;
			}

			$result = imagewebp( $image, $file_path, 80 );
			imagedestroy( $image );

			return $result;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Optimize GIF image
	 *
	 * @param string $file_path The file path.
	 * @return bool
	 */
	private function optimize_gif( $file_path ) {
		// GIF optimization is complex; for now just return true
		// In production, use external services
		return true;
	}

	/**
	 * Create WebP version of the image
	 *
	 * @param string $file_path The original file path.
	 * @return bool|string The WebP file path or false.
	 */
	private function create_webp_version( $file_path ) {
		if ( ! extension_loaded( 'gd' ) ) {
			return false;
		}

		$file_info = pathinfo( $file_path );
		$webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

		try {
			$file_type = wp_check_filetype( $file_path );
			$mime_type = $file_type['type'];

			$image = null;
			switch ( $mime_type ) {
				case 'image/jpeg':
					$image = imagecreatefromjpeg( $file_path );
					break;
				case 'image/png':
					$image = imagecreatefrompng( $file_path );
					break;
				case 'image/gif':
					$image = imagecreatefromgif( $file_path );
					break;
			}

			if ( ! $image || ! function_exists( 'imagewebp' ) ) {
				return false;
			}

			$result = imagewebp( $image, $webp_path, 80 );
			imagedestroy( $image );

			return $result ? $webp_path : false;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if a file can be optimized
	 *
	 * @param string $file_path The file path.
	 * @return bool
	 */
	private function is_optimizable( $file_path ) {
		$file_type = wp_check_filetype( $file_path );
		$mime_type = $file_type['type'];

		$optimizable_types = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		);

		return in_array( $mime_type, $optimizable_types, true );
	}

	/**
	 * Get optimization method for a file
	 *
	 * @param string $file_path The file path.
	 * @return string
	 */
	private function get_optimization_method( $file_path ) {
		// Check available external APIs or tools
		// Priority: external API > GD library > system tools
		return 'gd_library';
	}

	/**
	 * Check if WebP can be created
	 *
	 * @param string $file_path The file path.
	 * @return bool
	 */
	private function can_create_webp( $file_path ) {
		$file_type = wp_check_filetype( $file_path );
		$mime_type = $file_type['type'];

		// Only create WebP for JPEG and PNG
		if ( ! in_array( $mime_type, array( 'image/jpeg', 'image/png' ), true ) ) {
			return false;
		}

		return extension_loaded( 'gd' ) && function_exists( 'imagewebp' );
	}

	/**
	 * Get quality level for JPEG
	 *
	 * @param string $level The compression level.
	 * @return int
	 */
	private function get_quality_level( $level ) {
		$levels = array(
			'low'    => 95,
			'medium' => 85,
			'high'   => 75,
		);

		return $levels[ $level ] ?? 85;
	}

	/**
	 * Get compression level for PNG
	 *
	 * @param string $level The compression level.
	 * @return int
	 */
	private function get_compression_level( $level ) {
		$levels = array(
			'low'    => 6,
			'medium' => 7,
			'high'   => 9,
		);

		return $levels[ $level ] ?? 7;
	}

	/**
	 * Create a backup of the original file before optimization
	 *
	 * @param string $file_path The file path.
	 * @param int    $attachment_id The attachment ID.
	 * @return string The backup file path, or empty string if backup failed.
	 */
	private function create_backup( $file_path, $attachment_id ) {
		$backup_dir = dirname( $file_path ) . '/.backups';
		
		if ( ! wp_mkdir_p( $backup_dir ) ) {
			return '';
		}

		$file_info = pathinfo( $file_path );
		$backup_file = $backup_dir . '/' . $file_info['filename'] . '-' . $attachment_id . '-backup.' . $file_info['extension'];

		// Only create backup if it doesn't already exist
		if ( ! file_exists( $backup_file ) ) {
			if ( ! copy( $file_path, $backup_file ) ) {
				return '';
			}
		}

		return $backup_file;
	}

	/**
	 * Revert an optimized image to its original backup
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return array
	 */
	public function revert_optimization( $attachment_id ) {
		try {
			$file = get_attached_file( $attachment_id );

			if ( ! $file || ! file_exists( $file ) ) {
				return array(
					'success' => false,
					'error'   => 'File not found',
				);
			}

			// Get backup file path
			$file_info = pathinfo( $file );
			$backup_dir = dirname( $file ) . '/.backups';
			$backup_file = $backup_dir . '/' . $file_info['filename'] . '-' . $attachment_id . '-backup.' . $file_info['extension'];

			if ( ! file_exists( $backup_file ) ) {
				return array(
					'success' => false,
					'error'   => 'No backup found for this image',
				);
			}

			$optimized_size = filesize( $file );

			// Restore from backup
			if ( ! copy( $backup_file, $file ) ) {
				return array(
					'success' => false,
					'error'   => 'Failed to restore backup',
				);
			}

			$restored_size = filesize( $file );

			// Delete WebP versions if they exist
			$this->delete_webp_version( $file );

			// Update optimization result to mark as reverted
			Database::save_optimization_result(
				$attachment_id,
				array(
					'original_size'  => $restored_size,
					'optimized_size' => $optimized_size,
					'compression_ratio' => 0,
					'method'         => 'reverted',
					'webp_available' => false,
					'status'         => 'reverted',
				)
			);

			return array(
				'success'      => true,
				'restored_size' => $restored_size,
				'freed_space'  => $optimized_size - $restored_size,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}

	/**
	 * Delete WebP version of an image
	 *
	 * @param string $file_path The file path.
	 * @return bool
	 */
	private function delete_webp_version( $file_path ) {
		$webp_file = $file_path . '.webp';

		if ( file_exists( $webp_file ) ) {
			return unlink( $webp_file );
		}

		return true;
	}
}
