<?php

declare(strict_types=1);

/**
 * Settings Service
 *
 * Manages plugin performance toggle settings.
 * Allows users to enable/disable optimizations for A/B testing and customization.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Registers and manages plugin settings
 *
 * Provides a centralized location for all performance-related options.
 * Stores all settings in a single wp_option entry (odr_optimizer_settings)
 * to keep the options table clean.
 */
class SettingsService
{
    /**
     * Main option name for all plugin settings
     *
     * All performance toggles stored here as an associative array.
     * Example: ['preload_font' => 1, 'kill_bloat' => 1, 'inline_css' => 1]
     *
     * @var string
     */
    public const OPTION_NAME = 'odr_optimizer_settings';

    /**
     * Register settings and fields with WordPress
     *
     * Called on admin_init hook.
     * Registers the settings group and adds toggle fields.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the settings group for options.php form handling
        register_setting('odr_optimizer_group', self::OPTION_NAME);

        // Add settings section
        add_settings_section(
            'odr_perf_section',
            'Mobile Performance Boosters',
            '__return_null',
            'odr-optimizer',
        );

        // Register individual toggle fields
        $this->add_toggle('preload_fonts', 'Preload Theme Fonts');
        $this->add_toggle('kill_bloat', 'Disable Core Bloat (Emoji/Interactivity JS)');
        $this->add_toggle('inline_css', 'Inline Critical CSS');
        $this->add_toggle('lazy_load', 'Native Lazy Loading');
        $this->add_toggle('remove_emoji', 'Remove Emoji Detection Script');
        $this->add_toggle('font_swap', 'Use font-display: swap (faster text rendering)');
    }

    /**
     * Add a single toggle field to the settings form
     *
     * @param string $id    Field ID (used in HTML name/id attributes).
     * @param string $label User-facing label for the toggle.
     * @return void
     */
    private function add_toggle(string $id, string $label): void
    {
        add_settings_field(
            $id,
            $label,
            function () use ($id) {
                $options = $this->get_option();
                $checked = isset($options[ $id ]) && $options[ $id ] ? 'checked' : '';

                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                printf(
                    '<input type="checkbox" name="%s[%s]" value="1" %s /> Enable this optimization',
                    esc_attr(self::OPTION_NAME),
                    esc_attr($id),
                    $checked,
                );
                // phpcs:enable
            },
            'odr-optimizer',
            'odr_perf_section',
        );
    }

    /**
     * Get all plugin settings
     *
     * Returns the option array. If not set, returns empty array.
     *
     * @return array<string, mixed>
     */
    public static function get_option(): array
    {
        $option = get_option(self::OPTION_NAME);

        return is_array($option) ? $option : [];
    }

    /**
     * Check if a specific setting is enabled
     *
     * @param string $key The setting key to check.
     * @return bool True if the setting is enabled.
     */
    public static function is_enabled(string $key): bool
    {
        $options = self::get_option();

        return ! empty($options[ $key ]);
    }

    /**
     * Get a specific setting value
     *
     * @param string $key     The setting key.
     * @param mixed  $default Default value if not set.
     * @return mixed
     */
    public static function get_setting(string $key, $default = false)
    {
        $options = self::get_option();

        return $options[ $key ] ?? $default;
    }
}
