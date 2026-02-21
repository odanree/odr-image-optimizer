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
class Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ]);
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting(
            'image-optimizer-settings',
            'image_optimizer_settings',
            [
                'type'              => 'object',
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'show_in_rest'      => true,
            ],
        );

        // General Settings section
        add_settings_section(
            'image-optimizer-general',
            __('General Settings', 'odr-image-optimizer'),
            [ $this, 'render_section' ],
            'image-optimizer-settings',
        );

        add_settings_field(
            'compression_level',
            __('Compression Level', 'odr-image-optimizer'),
            [ $this, 'render_compression_field' ],
            'image-optimizer-settings',
            'image-optimizer-general',
        );

        add_settings_field(
            'enable_webp',
            __('Enable WebP Conversion', 'odr-image-optimizer'),
            [ $this, 'render_webp_field' ],
            'image-optimizer-settings',
            'image-optimizer-general',
        );

        add_settings_field(
            'enable_lazy_load',
            __('Enable Lazy Loading', 'odr-image-optimizer'),
            [ $this, 'render_lazy_load_field' ],
            'image-optimizer-settings',
            'image-optimizer-general',
        );

        add_settings_field(
            'auto_optimize',
            __('Auto-Optimize on Upload', 'odr-image-optimizer'),
            [ $this, 'render_auto_optimize_field' ],
            'image-optimizer-settings',
            'image-optimizer-general',
        );

        // Mobile Performance Boosters section (now consolidated into the same page)
        add_settings_section(
            'image-optimizer-performance',
            __('Mobile Performance Boosters', 'odr-image-optimizer'),
            [ $this, 'render_performance_section' ],
            'image-optimizer-settings',
        );

        add_settings_field(
            'preload_fonts',
            __('Preload Theme Fonts', 'odr-image-optimizer'),
            [ $this, 'render_preload_fonts_field' ],
            'image-optimizer-settings',
            'image-optimizer-performance',
        );

        add_settings_field(
            'kill_bloat',
            __('Remove Bloat (Emoji JS, Interactivity)', 'odr-image-optimizer'),
            [ $this, 'render_kill_bloat_field' ],
            'image-optimizer-settings',
            'image-optimizer-performance',
        );

        add_settings_field(
            'inline_critical_css',
            __('Inline Critical CSS', 'odr-image-optimizer'),
            [ $this, 'render_inline_css_field' ],
            'image-optimizer-settings',
            'image-optimizer-performance',
        );

        add_settings_field(
            'lazy_load_library',
            __('Defer Non-Critical Scripts', 'odr-image-optimizer'),
            [ $this, 'render_lazy_load_library_field' ],
            'image-optimizer-settings',
            'image-optimizer-performance',
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $settings The settings to sanitize.
     * @return array
     */
    public function sanitize_settings($settings)
    {
        if (! is_array($settings)) {
            $settings = [];
        }

        $sanitized = [
            'compression_level'   => in_array($settings['compression_level'] ?? 'medium', [ 'low', 'medium', 'high' ], true)
                ? $settings['compression_level']
                : 'medium',
            'enable_webp'         => ! empty($settings['enable_webp']),
            'enable_lazy_load'    => ! empty($settings['enable_lazy_load']),
            'auto_optimize'       => ! empty($settings['auto_optimize']),
            'preload_fonts'       => ! empty($settings['preload_fonts']),
            'kill_bloat'          => ! empty($settings['kill_bloat']),
            'inline_critical_css' => ! empty($settings['inline_critical_css']),
            'lazy_load_library'   => ! empty($settings['lazy_load_library']),
        ];

        return $sanitized;
    }

    /**
     * Enqueue scripts
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts($hook)
    {
        if ('image-optimizer_page_image-optimizer-settings' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'image-optimizer-settings',
            IMAGE_OPTIMIZER_URL . 'assets/css/settings.css',
            [],
            IMAGE_OPTIMIZER_VERSION,
        );
    }

    /**
     * Render settings section
     */
    public function render_section()
    {
        echo '<p>' . esc_html__('Configure image optimization settings.', 'odr-image-optimizer') . '</p>';
    }

    /**
     * Render performance section
     */
    public function render_performance_section()
    {
        echo '<p>' . esc_html__('Enable performance optimizations to improve mobile loading speed and Lighthouse scores.', 'odr-image-optimizer') . '</p>';
    }

    /**
     * Render compression level field
     */
    public function render_compression_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $value = $settings['compression_level'] ?? 'medium';
        ?>
		<select name="image_optimizer_settings[compression_level]">
			<option value="low" <?php selected($value, 'low'); ?>>
				<?php esc_html_e('Low (Better Quality)', 'odr-image-optimizer'); ?>
			</option>
			<option value="medium" <?php selected($value, 'medium'); ?>>
				<?php esc_html_e('Medium (Balanced)', 'odr-image-optimizer'); ?>
			</option>
			<option value="high" <?php selected($value, 'high'); ?>>
				<?php esc_html_e('High (Maximum Compression)', 'odr-image-optimizer'); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e('Select the compression level. Higher compression means smaller files but may reduce image quality.', 'odr-image-optimizer'); ?>
		</p>
		<?php
    }

    /**
     * Render WebP conversion field
     */
    public function render_webp_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $checked = ! empty($settings['enable_webp']);
        ?>
		<input type="checkbox" name="image_optimizer_settings[enable_webp]" value="1" <?php checked($checked); ?>>
		<label><?php esc_html_e('Automatically convert images to WebP format for better compression', 'odr-image-optimizer'); ?></label>
		<?php
    }

    /**
     * Render lazy loading field
     */
    public function render_lazy_load_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $checked = ! empty($settings['enable_lazy_load']);
        ?>
		<input type="checkbox" name="image_optimizer_settings[enable_lazy_load]" value="1" <?php checked($checked); ?>>
		<label><?php esc_html_e('Enable lazy loading for images', 'odr-image-optimizer'); ?></label>
		<?php
    }

    /**
     * Render auto-optimize field
     */
    public function render_auto_optimize_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $checked = ! empty($settings['auto_optimize']);
        ?>
		<input type="checkbox" name="image_optimizer_settings[auto_optimize]" value="1" <?php checked($checked); ?>>
		<label><?php esc_html_e('Automatically optimize images on upload', 'odr-image-optimizer'); ?></label>
		<?php
    }

    /**
     * Render preload fonts field
     */
    public function render_preload_fonts_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $checked = ! empty($settings['preload_fonts']);
        ?>
		<input type="checkbox" name="image_optimizer_settings[preload_fonts]" value="1" <?php checked($checked); ?>>
		<label><?php esc_html_e('Preload theme fonts to prevent FOUT (Flash of Unstyled Text)', 'odr-image-optimizer'); ?></label>
		<p class="description">
			<?php esc_html_e('Improves perceived performance by loading critical fonts early. Savings: 200-400ms on mobile.', 'odr-image-optimizer'); ?>
		</p>
		<?php
    }

    /**
     * Render kill bloat field
     */
    public function render_kill_bloat_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $checked = ! empty($settings['kill_bloat']);
        ?>
		<input type="checkbox" name="image_optimizer_settings[kill_bloat]" value="1" <?php checked($checked); ?>>
		<label><?php esc_html_e('Remove non-essential JavaScript (Emoji, Interactivity API)', 'odr-image-optimizer'); ?></label>
		<p class="description">
			<?php esc_html_e('Frees up bandwidth and processing for critical resources. Savings: 330-530ms on mobile.', 'odr-image-optimizer'); ?>
		</p>
		<?php
    }

    /**
     * Render inline CSS field
     */
    public function render_inline_css_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $checked = ! empty($settings['inline_critical_css']);
        ?>
		<input type="checkbox" name="image_optimizer_settings[inline_critical_css]" value="1" <?php checked($checked); ?>>
		<label><?php esc_html_e('Inline critical CSS above-the-fold', 'odr-image-optimizer'); ?></label>
		<p class="description">
			<?php esc_html_e('Reduces external CSS requests and unblocks rendering. Savings: 200-300ms on mobile.', 'odr-image-optimizer'); ?>
		</p>
		<?php
    }

    /**
     * Render lazy load library field
     */
    public function render_lazy_load_library_field()
    {
        $settings = get_option('image_optimizer_settings', []);
        $checked = ! empty($settings['lazy_load_library']);
        ?>
		<input type="checkbox" name="image_optimizer_settings[lazy_load_library]" value="1" <?php checked($checked); ?>>
		<label><?php esc_html_e('Defer non-critical JavaScript libraries', 'odr-image-optimizer'); ?></label>
		<p class="description">
			<?php esc_html_e('Prevents competing requests from blocking image loading. Works with native loading="lazy".', 'odr-image-optimizer'); ?>
		</p>
		<?php
    }

    /**
     * Render settings page
     */
    public static function render()
    {
        ?>
		<div class="wrap image-optimizer-settings-wrap">
			<h1><?php esc_html_e('ODR Image Optimizer Settings', 'odr-image-optimizer'); ?></h1>
			<form method="post" action="options.php">
				<?php
                settings_fields('image-optimizer-settings');
        do_settings_sections('image-optimizer-settings');
        submit_button();
        ?>
			</form>
		</div>
		<?php

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

    }
}
