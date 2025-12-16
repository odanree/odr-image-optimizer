<?php
/**
 * Plugin Settings
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

/**
 * Settings class
 */
class Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'image-optimizer-settings',
			'image_optimizer_settings',
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => true,
			)
		);

		add_settings_section(
			'image-optimizer-general',
			__( 'General Settings', 'image-optimizer' ),
			array( $this, 'render_section' ),
			'image-optimizer-settings'
		);

		add_settings_field(
			'compression_level',
			__( 'Compression Level', 'image-optimizer' ),
			array( $this, 'render_compression_field' ),
			'image-optimizer-settings',
			'image-optimizer-general'
		);

		add_settings_field(
			'enable_webp',
			__( 'Enable WebP Conversion', 'image-optimizer' ),
			array( $this, 'render_webp_field' ),
			'image-optimizer-settings',
			'image-optimizer-general'
		);

		add_settings_field(
			'enable_lazy_load',
			__( 'Enable Lazy Loading', 'image-optimizer' ),
			array( $this, 'render_lazy_load_field' ),
			'image-optimizer-settings',
			'image-optimizer-general'
		);

		add_settings_field(
			'auto_optimize',
			__( 'Auto-Optimize on Upload', 'image-optimizer' ),
			array( $this, 'render_auto_optimize_field' ),
			'image-optimizer-settings',
			'image-optimizer-general'
		);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $settings The settings to sanitize.
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$sanitized = array(
			'compression_level' => in_array( $settings['compression_level'] ?? 'medium', array( 'low', 'medium', 'high' ), true )
				? $settings['compression_level']
				: 'medium',
			'enable_webp'       => ! empty( $settings['enable_webp'] ),
			'enable_lazy_load'  => ! empty( $settings['enable_lazy_load'] ),
			'auto_optimize'     => ! empty( $settings['auto_optimize'] ),
		);

		return $sanitized;
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'image-optimizer_page_image-optimizer-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'image-optimizer-settings',
			IMAGE_OPTIMIZER_URL . 'assets/css/settings.css',
			array(),
			IMAGE_OPTIMIZER_VERSION
		);
	}

	/**
	 * Render settings section
	 */
	public function render_section() {
		echo '<p>' . esc_html__( 'Configure image optimization settings.', 'image-optimizer' ) . '</p>';
	}

	/**
	 * Render compression level field
	 */
	public function render_compression_field() {
		$settings = get_option( 'image_optimizer_settings', array() );
		$value = $settings['compression_level'] ?? 'medium';
		?>
		<select name="image_optimizer_settings[compression_level]">
			<option value="low" <?php selected( $value, 'low' ); ?>>
				<?php esc_html_e( 'Low (Better Quality)', 'image-optimizer' ); ?>
			</option>
			<option value="medium" <?php selected( $value, 'medium' ); ?>>
				<?php esc_html_e( 'Medium (Balanced)', 'image-optimizer' ); ?>
			</option>
			<option value="high" <?php selected( $value, 'high' ); ?>>
				<?php esc_html_e( 'High (Maximum Compression)', 'image-optimizer' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the compression level. Higher compression means smaller files but may reduce image quality.', 'image-optimizer' ); ?>
		</p>
		<?php
	}

	/**
	 * Render WebP conversion field
	 */
	public function render_webp_field() {
		$settings = get_option( 'image_optimizer_settings', array() );
		$checked = ! empty( $settings['enable_webp'] );
		?>
		<input type="checkbox" name="image_optimizer_settings[enable_webp]" value="1" <?php checked( $checked ); ?>>
		<label><?php esc_html_e( 'Automatically convert images to WebP format for better compression', 'image-optimizer' ); ?></label>
		<?php
	}

	/**
	 * Render lazy loading field
	 */
	public function render_lazy_load_field() {
		$settings = get_option( 'image_optimizer_settings', array() );
		$checked = ! empty( $settings['enable_lazy_load'] );
		?>
		<input type="checkbox" name="image_optimizer_settings[enable_lazy_load]" value="1" <?php checked( $checked ); ?>>
		<label><?php esc_html_e( 'Enable lazy loading for images', 'image-optimizer' ); ?></label>
		<?php
	}

	/**
	 * Render auto-optimize field
	 */
	public function render_auto_optimize_field() {
		$settings = get_option( 'image_optimizer_settings', array() );
		$checked = ! empty( $settings['auto_optimize'] );
		?>
		<input type="checkbox" name="image_optimizer_settings[auto_optimize]" value="1" <?php checked( $checked ); ?>>
		<label><?php esc_html_e( 'Automatically optimize images on upload', 'image-optimizer' ); ?></label>
		<?php
	}

	/**
	 * Render settings page
	 */
	public static function render() {
		?>
		<div class="wrap image-optimizer-settings-wrap">
			<h1><?php esc_html_e( 'Image Optimizer Settings', 'image-optimizer' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'image-optimizer-settings' );
				do_settings_sections( 'image-optimizer-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
