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
     * Registers WordPress settings that match the SOA Settings_Repository defaults.
     * These are the actual performance optimization flags used by services.
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
            'odr_optimizer_settings',
            [
                'type'              => 'object',
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'show_in_rest'      => true,
            ],
        );

        // HTTP Transport Optimization
        add_settings_section(
            'odr_http_transport',
            esc_html__('HTTP Transport Optimization', 'odr-image-optimizer'),
            [ $this, 'render_http_section' ],
            'image-optimizer-settings',
        );

        add_settings_field(
            'odr_enable_gzip',
            esc_html__('Enable PHP Gzip Compression', 'odr-image-optimizer'),
            [ $this, 'render_gzip_field' ],
            'image-optimizer-settings',
            'odr_http_transport',
        );

        // Critical Rendering Path
        add_settings_section(
            'odr_critical_path',
            esc_html__('Critical Rendering Path', 'odr-image-optimizer'),
            [ $this, 'render_critical_path_section' ],
            'image-optimizer-settings',
        );

        add_settings_field(
            'odr_preload_fonts',
            esc_html__('Priority 0 Font Preloading', 'odr-image-optimizer'),
            [ $this, 'render_preload_fonts_field' ],
            'image-optimizer-settings',
            'odr_critical_path',
        );

        add_settings_field(
            'odr_fix_font_display',
            esc_html__('Fix Font Display (swap)', 'odr-image-optimizer'),
            [ $this, 'render_fix_font_display_field' ],
            'image-optimizer-settings',
            'odr_critical_path',
        );

        add_settings_field(
            'odr_inject_lcp_preload',
            esc_html__('Inject LCP Image Preload', 'odr-image-optimizer'),
            [ $this, 'render_inject_lcp_field' ],
            'image-optimizer-settings',
            'odr_critical_path',
        );

        // Aggressive Optimizations
        add_settings_section(
            'odr_aggressive',
            esc_html__('Aggressive Optimizations (Advanced)', 'odr-image-optimizer'),
            [ $this, 'render_aggressive_section' ],
            'image-optimizer-settings',
        );

        add_settings_field(
            'odr_remove_bloat',
            esc_html__('Aggressive Core Bloat Removal', 'odr-image-optimizer'),
            [ $this, 'render_remove_bloat_field' ],
            'image-optimizer-settings',
            'odr_aggressive',
        );

        add_settings_field(
            'odr_aggressive_mode',
            esc_html__('Enable All Aggressive Features', 'odr-image-optimizer'),
            [ $this, 'render_aggressive_mode_field' ],
            'image-optimizer-settings',
            'odr_aggressive',
        );

        add_settings_field(
            'odr_fix_nested_lists',
            esc_html__('Fix Nested Lists (HTML Sanitization)', 'odr-image-optimizer'),
            [ $this, 'render_fix_nested_lists_field' ],
            'image-optimizer-settings',
            'odr_aggressive',
        );

        add_settings_field(
            'odr_inject_seo_meta',
            esc_html__('Inject SEO Meta Tags', 'odr-image-optimizer'),
            [ $this, 'render_inject_seo_meta_field' ],
            'image-optimizer-settings',
            'odr_aggressive',
        );
    }

    /**
     * Sanitize and validate settings
     *
     * Ensures all settings are properly validated before saving to database.
     * Only allows boolean values for the SOA settings.
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
            'enable_gzip'         => ! empty($settings['enable_gzip']),
            'preload_fonts'       => ! empty($settings['preload_fonts']),
            'inject_lcp_preload'  => ! empty($settings['inject_lcp_preload']),
            'inject_seo_meta'     => ! empty($settings['inject_seo_meta']),
            'fix_font_display'    => ! empty($settings['fix_font_display']),
            'fix_nested_lists'    => ! empty($settings['fix_nested_lists']),
            'remove_bloat'        => ! empty($settings['remove_bloat']),
            'aggressive_mode'     => ! empty($settings['aggressive_mode']),
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
     * Render HTTP transport section
     */
    public function render_http_section(): void
    {
        echo '<p>' . esc_html__('Optimize how responses are delivered to browsers.', 'odr-image-optimizer') . '</p>';
    }

    /**
     * Render critical rendering path section
     */
    public function render_critical_path_section(): void
    {
        echo '<p>' . esc_html__('Speed up the browser\'s critical rendering path.', 'odr-image-optimizer') . '</p>';
    }

    /**
     * Render aggressive optimizations section
     */
    public function render_aggressive_section(): void
    {
        echo '<p>' . esc_html__('Advanced features that may impact compatibility. Test thoroughly before enabling.', 'odr-image-optimizer') . '</p>';
    }

    /**
     * Render gzip field
     */
    public function render_gzip_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['enable_gzip']);
        ?>
        <input type="checkbox" id="enable_gzip" name="odr_optimizer_settings[enable_gzip]" value="1" <?php checked($checked); ?>>
        <label for="enable_gzip"><?php esc_html_e('Enable PHP Gzip compression for all responses', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Reduces response size by ~60-70%. Required for Lighthouse 100/100.', 'odr-image-optimizer'); ?></p>
        <?php
    }

    /**
     * Render preload fonts field
     */
    public function render_preload_fonts_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['preload_fonts']);
        ?>
        <input type="checkbox" id="preload_fonts" name="odr_optimizer_settings[preload_fonts]" value="1" <?php checked($checked); ?>>
        <label for="preload_fonts"><?php esc_html_e('Preload theme fonts with priority 0', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Breaks CSS → Font discovery chain. Required for Lighthouse 100/100.', 'odr-image-optimizer'); ?></p>
        <?php
    }

    /**
     * Render fix font display field
     */
    public function render_fix_font_display_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['fix_font_display']);
        ?>
        <input type="checkbox" id="fix_font_display" name="odr_optimizer_settings[fix_font_display]" value="1" <?php checked($checked); ?>>
        <label for="fix_font_display"><?php esc_html_e('Fix font-display: swap (prevent FOUT)', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Ensures all fonts use swap to show fallback text immediately.', 'odr-image-optimizer'); ?></p>
        <?php
    }

    /**
     * Render inject LCP preload field
     */
    public function render_inject_lcp_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['inject_lcp_preload']);
        ?>
        <input type="checkbox" id="inject_lcp_preload" name="odr_optimizer_settings[inject_lcp_preload]" value="1" <?php checked($checked); ?>>
        <label for="inject_lcp_preload"><?php esc_html_e('Inject LCP image preload hints', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Auto-detects and preloads Largest Contentful Paint images.', 'odr-image-optimizer'); ?></p>
        <?php
    }

    /**
     * Render remove bloat field
     */
    public function render_remove_bloat_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['remove_bloat']);
        ?>
        <input type="checkbox" id="remove_bloat" name="odr_optimizer_settings[remove_bloat]" value="1" <?php checked($checked); ?>>
        <label for="remove_bloat"><?php esc_html_e('Remove core bloat (emoji, interactivity API)', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Removes non-essential WordPress features. Required for Lighthouse 100/100.', 'odr-image-optimizer'); ?></p>
        <?php
    }

    /**
     * Render aggressive mode field
     */
    public function render_aggressive_mode_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['aggressive_mode']);
        ?>
        <input type="checkbox" id="aggressive_mode" name="odr_optimizer_settings[aggressive_mode]" value="1" <?php checked($checked); ?>>
        <label for="aggressive_mode"><?php esc_html_e('Enable all aggressive features', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Master toggle for all advanced optimizations.', 'odr-image-optimizer'); ?></p>
        <?php
    }

    /**
     * Render fix nested lists field
     */
    public function render_fix_nested_lists_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['fix_nested_lists']);
        ?>
        <input type="checkbox" id="fix_nested_lists" name="odr_optimizer_settings[fix_nested_lists]" value="1" <?php checked($checked); ?>>
        <label for="fix_nested_lists"><?php esc_html_e('Sanitize nested list HTML', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Fixes malformed HTML in nested lists. May break some themes.', 'odr-image-optimizer'); ?></p>
        <?php
    }

    /**
     * Render inject SEO meta field
     */
    public function render_inject_seo_meta_field(): void
    {
        $settings = get_option('odr_optimizer_settings', []);
        $checked = ! empty($settings['inject_seo_meta']);
        ?>
        <input type="checkbox" id="inject_seo_meta" name="odr_optimizer_settings[inject_seo_meta]" value="1" <?php checked($checked); ?>>
        <label for="inject_seo_meta"><?php esc_html_e('Inject SEO meta tags', 'odr-image-optimizer'); ?></label>
        <p class="description"><?php esc_html_e('Adds Open Graph and structured data for better search visibility.', 'odr-image-optimizer'); ?></p>
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
        <form method="post" action="options.php" class="image-optimizer-settings-form">
            <?php
            settings_fields('image-optimizer-settings');
            do_settings_sections('image-optimizer-settings');
            submit_button();
            ?>
        </form>
        <?php
    }
}
