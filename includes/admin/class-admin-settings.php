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
     * Settings menu is now managed by class-core.php which registers under Tools menu.
     * This method is kept for backward compatibility but does not register a menu.
     * The Settings_Repository and Admin_Settings provide the configuration layer.
     *
     * @return void
     */
    public function add_menu_page(): void
    {
        // Menu registration is handled by class-core.php
        // This class provides the settings repository and UI rendering
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

        // Add settings section for performance optimizations
        add_settings_section(
            'odr_performance_section',
            '🎯 400/400 Performance Sweeps',
            null,
            'image-optimizer-settings',  // Must match menu slug above
        );

        // Add individual toggles for each optimization
        $this->add_toggle('enable_gzip', 'Enable PHP Gzip Compression');
        $this->add_toggle('force_font_swap', 'Priority 0 Font Preloading');
        $this->add_toggle('remove_bloat', 'Aggressive Core Bloat Removal');
    }

    /**
     * Add a single checkbox toggle field
     *
     * @param string $id    The setting key/ID
     * @param string $label The label for the checkbox
     * @return void
     */
    private function add_toggle(string $id, string $label): void
    {
        add_settings_field(
            $id,
            esc_html($label),
            function () use ($id) {
                $options = get_option('odr_optimizer_settings', []);
                $checked = isset($options[$id]) ? 'checked' : '';
                printf(
                    '<input type="checkbox" id="%s" name="odr_optimizer_settings[%s]" value="1" %s />',
                    esc_attr($id),
                    esc_attr($id),
                    $checked,
                );
                echo '<p class="description">Required for Lighthouse 100/100.</p>';
            },
            'image-optimizer-settings',  // Must match menu slug
            'odr_performance_section',
        );
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
            <h1>ODR Image Optimizer <span style="color: #46b450;">100/100</span></h1>

            <form method="post" action="options.php">
                <?php
                    settings_fields('odr_optimizer_group');
        do_settings_sections('image-optimizer-settings');
        submit_button('Save Performance Settings');
        ?>
            </form>
        </div>
        <?php
    }
}
