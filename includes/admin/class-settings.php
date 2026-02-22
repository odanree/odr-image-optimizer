<?php

declare(strict_types=1);

/**
 * Plugin Settings UI
 *
 * Manages the WordPress admin settings page.
 * Settings are registered to the 'image-optimizer-settings' page.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Settings page class
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
        add_action('admin_notices', [ $this, 'show_save_notice' ]);
    }

    /**
     * Register settings
     *
     * Adds capability check to ensure only admins can save settings.
     *
     * @return void
     */
    public function register_settings(): void
    {
        // Capability check: only allow manage_options
        if (! current_user_can('manage_options')) {
            return;
        }

        register_setting(
            'image-optimizer-settings',
            'odr_image_optimizer_settings',
            [
                'type'              => 'object',
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'show_in_rest'      => true,
            ],
        );

        // Media Policy section
        add_settings_section(
            'odr_media_policy',
            esc_html__('Image Optimization (Media Policy)', 'odr-image-optimizer'),
            [ $this, 'render_media_section' ],
            'image-optimizer-settings',
        );

        add_settings_field(
            'odr_compression_level',
            esc_html__('Compression Level', 'odr-image-optimizer'),
            [ $this, 'render_compression_field' ],
            'image-optimizer-settings',
            'odr_media_policy',
        );

        add_settings_field(
            'odr_enable_webp',
            esc_html__('WebP Format Support', 'odr-image-optimizer'),
            [ $this, 'render_webp_field' ],
            'image-optimizer-settings',
            'odr_media_policy',
        );

        add_settings_field(
            'odr_auto_optimize',
            esc_html__('Auto-Optimize on Upload', 'odr-image-optimizer'),
            [ $this, 'render_auto_optimize_field' ],
            'image-optimizer-settings',
            'odr_media_policy',
        );

        // Delivery Policy section
        add_settings_section(
            'odr_delivery_policy',
            esc_html__('Frontend Performance (Delivery Policy)', 'odr-image-optimizer'),
            [ $this, 'render_delivery_section' ],
            'image-optimizer-settings',
        );

        add_settings_field(
            'odr_lazy_load_mode',
            esc_html__('Lazy Loading Mode', 'odr-image-optimizer'),
            [ $this, 'render_lazy_load_field' ],
            'image-optimizer-settings',
            'odr_delivery_policy',
        );

        add_settings_field(
            'odr_preload_fonts',
            esc_html__('Preload Theme Fonts', 'odr-image-optimizer'),
            [ $this, 'render_preload_fonts_field' ],
            'image-optimizer-settings',
            'odr_delivery_policy',
        );

        add_settings_field(
            'odr_kill_bloat',
            esc_html__('Remove Bloat Scripts', 'odr-image-optimizer'),
            [ $this, 'render_kill_bloat_field' ],
            'image-optimizer-settings',
            'odr_delivery_policy',
        );

        add_settings_field(
            'odr_inline_critical_css',
            esc_html__('Inline Critical CSS', 'odr-image-optimizer'),
            [ $this, 'render_inline_css_field' ],
            'image-optimizer-settings',
            'odr_delivery_policy',
        );
    }

    /**
     * Sanitize settings
     *
     * @param array $settings The settings to sanitize.
     * @return array
     */
    /**
     * Sanitize and validate settings
     *
     * Called by WordPress settings API. Nonce is verified automatically
     * by register_setting() when using settings_fields() form output.
     *
     * Security:
     * - Nonce verification: Automatic via settings_fields()
     * - Capability check: Enforced in register_settings()
     * - Type validation: Explicit for each setting
     *
     * @param mixed $settings The raw settings array.
     * @return array Sanitized and validated settings.
     */
    public function sanitize_settings($settings): array
    {
        if (! is_array($settings)) {
            $settings = [];
        }

        return [
            'compression_level'   => in_array($settings['compression_level'] ?? 'medium', [ 'low', 'medium', 'high' ], true)
                ? $settings['compression_level']
                : 'medium',
            'enable_webp'         => ! empty($settings['enable_webp']),
            'lazy_load_mode'      => in_array($settings['lazy_load_mode'] ?? 'native', [ 'native', 'hybrid', 'off' ], true)
                ? $settings['lazy_load_mode']
                : 'native',
            'auto_optimize'       => ! empty($settings['auto_optimize']),
            'preload_fonts'       => ! empty($settings['preload_fonts']),
            'kill_bloat'          => ! empty($settings['kill_bloat']),
            'inline_critical_css' => ! empty($settings['inline_critical_css']),
        ];
    }

    /**
     * Enqueue scripts
     *
     * @param string $hook The current admin page.
     * @return void
     */
    public function enqueue_scripts($hook): void
    {
        // Hook name for submenu page under tools.php: tools_page_{page_slug}
        if ('tools_page_image-optimizer-settings' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'odr-image-optimizer-settings',
            IMAGE_OPTIMIZER_URL . 'assets/css/settings.css',
            [],
            IMAGE_OPTIMIZER_VERSION,
        );
    }

    /**
     * Show save notice
     *
     * @return void
     */
    public function show_save_notice(): void
    {
        if (! isset($_GET['settings-updated'])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>';
        esc_html_e('Settings saved successfully.', 'odr-image-optimizer');
        echo '</p></div>';
    }

    /**
     * Render media policy section
     *
     * @return void
     */
    public function render_media_section(): void
    {
        echo '<p>' . esc_html__('Control how images are processed and optimized on upload.', 'odr-image-optimizer') . '</p>';
    }

    /**
     * Render delivery policy section
     *
     * @return void
     */
    public function render_delivery_section(): void
    {
        echo '<p>' . esc_html__('Control how images are delivered and rendered on the frontend.', 'odr-image-optimizer') . '</p>';
    }

    /**
     * Render compression level field
     *
     * @return void
     */
    public function render_compression_field(): void
    {
        $settings = get_option('odr_image_optimizer_settings', []);
        $value = $settings['compression_level'] ?? 'medium';
        ?>
        <select name="odr_image_optimizer_settings[compression_level]">
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
            <?php esc_html_e('Select compression level. Higher compression means smaller files but may reduce image quality.', 'odr-image-optimizer'); ?>
        </p>
        <?php
    }

    /**
     * Render WebP conversion field
     *
     * @return void
     */
    public function render_webp_field(): void
    {
        $settings = get_option('odr_image_optimizer_settings', []);
        $checked = ! empty($settings['enable_webp']);
        ?>
        <input type="checkbox" name="odr_image_optimizer_settings[enable_webp]" value="1" <?php checked($checked); ?>>
        <label><?php esc_html_e('Automatically convert images to WebP format for better compression', 'odr-image-optimizer'); ?></label>
        <?php
    }

    /**
     * Render lazy loading mode field
     *
     * @return void
     */
    public function render_lazy_load_field(): void
    {
        $settings = get_option('odr_image_optimizer_settings', []);
        $mode = $settings['lazy_load_mode'] ?? 'native';
        ?>
        <select name="odr_image_optimizer_settings[lazy_load_mode]">
            <option value="native" <?php selected($mode, 'native'); ?>>
                <?php esc_html_e('Native (Browser-Based)', 'odr-image-optimizer'); ?>
            </option>
            <option value="hybrid" <?php selected($mode, 'hybrid'); ?>>
                <?php esc_html_e('Hybrid (JS Fallback)', 'odr-image-optimizer'); ?>
            </option>
            <option value="off" <?php selected($mode, 'off'); ?>>
                <?php esc_html_e('Disabled', 'odr-image-optimizer'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Native (default) uses browser loading="lazy" for best performance. Hybrid adds JS fallback for older browsers.', 'odr-image-optimizer'); ?>
        </p>
        <?php
    }

    /**
     * Render auto-optimize field
     *
     * @return void
     */
    public function render_auto_optimize_field(): void
    {
        $settings = get_option('odr_image_optimizer_settings', []);
        $checked = ! empty($settings['auto_optimize']);
        ?>
        <input type="checkbox" name="odr_image_optimizer_settings[auto_optimize]" value="1" <?php checked($checked); ?>>
        <label><?php esc_html_e('Automatically optimize images on upload', 'odr-image-optimizer'); ?></label>
        <?php
    }

    /**
     * Render preload fonts field
     *
     * @return void
     */
    public function render_preload_fonts_field(): void
    {
        $settings = get_option('odr_image_optimizer_settings', []);
        $checked = ! empty($settings['preload_fonts']);
        ?>
        <input type="checkbox" name="odr_image_optimizer_settings[preload_fonts]" value="1" <?php checked($checked); ?>>
        <label><?php esc_html_e('Preload theme fonts to prevent Flash of Unstyled Text', 'odr-image-optimizer'); ?></label>
        <p class="description">
            <?php esc_html_e('Improves perceived performance by loading critical fonts early.', 'odr-image-optimizer'); ?>
        </p>
        <?php
    }

    /**
     * Render kill bloat field
     *
     * @return void
     */
    public function render_kill_bloat_field(): void
    {
        $settings = get_option('odr_image_optimizer_settings', []);
        $checked = ! empty($settings['kill_bloat']);
        ?>
        <input type="checkbox" name="odr_image_optimizer_settings[kill_bloat]" value="1" <?php checked($checked); ?>>
        <label><?php esc_html_e('Remove non-essential JavaScript (Emoji detection, Interactivity API)', 'odr-image-optimizer'); ?></label>
        <p class="description">
            <?php esc_html_e('Frees up bandwidth and processing for critical resources.', 'odr-image-optimizer'); ?>
        </p>
        <?php
    }

    /**
     * Render inline CSS field
     *
     * @return void
     */
    public function render_inline_css_field(): void
    {
        $settings = get_option('odr_image_optimizer_settings', []);
        $checked = ! empty($settings['inline_critical_css']);
        ?>
        <input type="checkbox" name="odr_image_optimizer_settings[inline_critical_css]" value="1" <?php checked($checked); ?>>
        <label><?php esc_html_e('Inline critical CSS above-the-fold', 'odr-image-optimizer'); ?></label>
        <p class="description">
            <?php esc_html_e('Reduces external CSS requests and unblocks rendering.', 'odr-image-optimizer'); ?>
        </p>
        <?php
    }

    /**
     * Render settings page
     *
     * @return void
     */
    public static function render(): void
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
    }
}
