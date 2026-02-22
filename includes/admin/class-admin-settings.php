<?php

declare(strict_types=1);

/**
 * Admin Settings UI
 *
 * Handles the WordPress admin menu and settings form rendering.
 * Has a single responsibility: Display and save settings in WordPress admin.
 *
 * Follows: Single Responsibility Principle (SRP)
 * - Only handles UI/form display
 * - Doesn't contain business logic
 * - Doesn't know how settings are stored (that's Settings_Repository's job)
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

use ImageOptimizer\Services\Settings_Repository;

/**
 * Admin_Settings: WordPress admin UI for plugin configuration
 *
 * Provides settings page where users can:
 * - Enable/disable individual optimizations
 * - Choose between Safe and Aggressive modes
 * - See help text explaining each setting
 * - Understand impact of each optimization
 */
class Admin_Settings
{
    /**
     * Settings repository for accessing current settings
     *
     * @var Settings_Repository
     */
    private Settings_Repository $settings;

    /**
     * Constructor: Inject settings repository
     *
     * Dependency Injection ensures this class doesn't need to know
     * about WordPress get_option() - it just uses the repository.
     *
     * @param Settings_Repository $settings The settings repository instance
     */
    public function __construct(Settings_Repository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Register hooks for admin functionality
     *
     * Called during plugin initialization to set up admin hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add plugin settings page to admin menu
     *
     * Creates "Image Optimizer" submenu under Settings.
     *
     * @return void
     */
    public function add_menu_page(): void
    {
        add_options_page(
            'ODR Image Optimizer Settings',
            'Image Optimizer',
            'manage_options',
            'odr-optimizer',
            [$this, 'render_form'],
        );
    }

    /**
     * Register WordPress settings and fields
     *
     * Sets up the settings group and individual fields that will be
     * saved to the options table via settings_fields() and do_settings_sections().
     *
     * @return void
     */
    public function register_settings(): void
    {
        // Register the main settings option
        register_setting('odr_optimizer_group', 'odr_optimizer_settings', [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        // ===== SAFE MODE SECTION =====
        add_settings_section(
            'odr_safe_section',
            'Safe Mode (Recommended)',
            [$this, 'render_safe_section_description'],
            'odr-optimizer',
        );

        foreach (Settings_Repository::get_settings_by_category('safe') as $key => $setting) {
            $this->add_checkbox_field($key, $setting);
        }

        // ===== AGGRESSIVE MODE SECTION =====
        add_settings_section(
            'odr_aggressive_section',
            'Aggressive Mode (Advanced Users)',
            [$this, 'render_aggressive_section_description'],
            'odr-optimizer',
        );

        foreach (Settings_Repository::get_settings_by_category('aggressive') as $key => $setting) {
            $this->add_checkbox_field($key, $setting);
        }
    }

    /**
     * Render description for Safe Mode section
     *
     * @return void
     */
    public function render_safe_section_description(): void
    {
        echo '<p>';
        echo 'These settings are <strong>recommended for all sites</strong>. They are proven safe and ';
        echo 'contribute directly to the 400/400 Lighthouse score. Enable all for best results.';
        echo '</p>';
    }

    /**
     * Render description for Aggressive Mode section
     *
     * @return void
     */
    public function render_aggressive_section_description(): void
    {
        echo '<p>';
        echo '<strong style="color: #dc3545;">⚠️ Warning:</strong> These are advanced optimizations that may break ';
        echo 'some sites. Always test on staging first. Recommended only for sites using modern block-based themes.';
        echo '</p>';
    }

    /**
     * Add a single checkbox field
     *
     * @param string                $key     The setting key
     * @param array<string, string> $setting The setting metadata (label, description, etc.)
     * @return void
     */
    private function add_checkbox_field(string $key, array $setting): void
    {
        add_settings_field(
            $key,
            esc_html($setting['label'] ?? $key),
            [$this, 'render_checkbox_field'],
            'odr-optimizer',
            'odr_' . ($setting['category'] ?? 'safe') . '_section',
            ['key' => $key, 'setting' => $setting],
        );
    }

    /**
     * Render a checkbox field in the settings form
     *
     * @param array<string, mixed> $args Field arguments with 'key' and 'setting'
     * @return void
     */
    public function render_checkbox_field(array $args = []): void
    {
        $key = $args['key'] ?? '';
        $setting = $args['setting'] ?? [];

        if (empty($key)) {
            return;
        }

        $all_settings = get_option('odr_optimizer_settings', []);
        $is_checked = isset($all_settings[$key]) && $all_settings[$key];

        // Checkbox input
        printf(
            '<input type="checkbox" id="%s" name="odr_optimizer_settings[%s]" value="1" %s />',
            esc_attr($key),
            esc_attr($key),
            $is_checked ? 'checked="checked"' : '',
        );

        // Help text below checkbox
        if (! empty($setting['description'])) {
            printf(
                '<p class="description" style="margin-top: 0.5em;">%s</p>',
                esc_html($setting['description']),
            );
        }
    }

    /**
     * Sanitize settings before saving to database
     *
     * Ensures only expected keys are saved, and values are normalized.
     * This is a security measure against injection attacks.
     *
     * @param mixed $input The raw input from $_POST
     * @return array<string, bool> Sanitized settings
     */
    public function sanitize_settings($input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $sanitized = [];
        $available_keys = array_keys(Settings_Repository::get_available_settings());

        foreach ($input as $key => $value) {
            // Only save whitelisted keys
            if (in_array($key, $available_keys, true)) {
                // Convert to boolean (checkbox values are '1' or unchecked)
                $sanitized[$key] = (bool) $value;
            }
        }

        return $sanitized;
    }

    /**
     * Render the complete settings form
     *
     * This is the main page that users see in WordPress admin.
     *
     * @return void
     */
    public function render_form(): void
    {
        // Check user permissions
        if (! current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div style="max-width: 800px; margin-top: 20px;">
                <div style="background: #f0f6fc; border: 1px solid #b0d4e0; border-radius: 4px; padding: 12px; margin-bottom: 20px;">
                    <strong>🎯 Goal:</strong> Achieve and maintain <strong>400/400 Lighthouse score</strong>
                    <br>
                    <small style="color: #555;">
                        Below are the optimizations that contribute to this perfect score. 
                        Enable safe options for all sites, enable aggressive options only if testing on staging.
                    </small>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('odr_optimizer_group'); ?>
                    <?php do_settings_sections('odr-optimizer'); ?>
                    <?php submit_button('Save Settings', 'primary'); ?>
                </form>

                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; margin-top: 20px;">
                    <strong>💡 Tip:</strong> All safe mode options are enabled by default. 
                    Toggle aggressive options only if you encounter issues on block-based themes.
                </div>
            </div>
        </div>
        <?php
    }
}
