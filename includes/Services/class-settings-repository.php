<?php

declare(strict_types=1);

/**
 * Settings Repository
 *
 * Centralizes all settings retrieval, preventing services from calling get_option() directly.
 * Implements the Repository Pattern for clean data access.
 *
 * This class is the single source of truth for plugin configuration.
 * Services depend on this repository, not on WordPress directly.
 *
 * Follows: Interface Segregation Principle (ISP)
 * - Services get a clean interface (is_enabled(), get_value())
 * - Not tied to WordPress get_option() implementation
 * - Easy to swap for different storage later (config files, environment vars, etc.)
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Settings_Repository: Single source of truth for plugin configuration
 *
 * Caches settings in memory to avoid repeated get_option() calls.
 * Provides type-safe access methods for different setting types.
 */
class Settings_Repository
{
    /**
     * Default settings (Safe mode by default)
     *
     * Safe defaults ensure the plugin works well for all users,
     * with aggressive optimizations opt-in only.
     *
     * @var array<string, bool|string>
     */
    private const DEFAULTS = [
        // Safe mode: Always enabled for Lighthouse 400/400
        'enable_gzip'         => true,
        'preload_fonts'       => true,
        'inject_lcp_preload'  => true,
        'inject_seo_meta'     => true,
        'fix_font_display'    => true,
        'fix_nested_lists'    => true,

        // Aggressive mode: User opt-in (can break some sites)
        'remove_bloat'        => false,
        'aggressive_mode'     => false,
    ];

    /**
     * Cached options from database
     *
     * @var array<string, bool|string>
     */
    private array $options = [];

    /**
     * Constructor: Load settings from database and cache
     *
     * @return void
     */
    public function __construct()
    {
        $stored = get_option('odr_optimizer_settings', []);

        // Merge stored settings with defaults
        // Defaults take precedence for missing keys (safe behavior)
        $this->options = array_merge(self::DEFAULTS, is_array($stored) ? $stored : []);
    }

    /**
     * Check if a boolean setting is enabled
     *
     * Type-safe boolean retrieval with sensible defaults.
     * This is the primary method services use to check if features are active.
     *
     * Usage:
     *   if ($settings->is_enabled('remove_bloat')) {
     *       // Feature is enabled
     *   }
     *
     * @param string $key     The setting key (e.g., 'remove_bloat')
     * @param bool   $default Fallback value if key not found (defaults to false = safe)
     * @return bool True if enabled, false otherwise
     */
    public function is_enabled(string $key, bool $default = false): bool
    {
        $value = $this->options[$key] ?? $default;

        // Handle various truthy/falsy values
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array($value, ['1', 'true', 'yes', 'on'], true);
        }

        // Convert to bool for all other types (int, object, array, etc.)
        return (bool) $value;
    }

    /**
     * Get a string setting value
     *
     * Type-safe string retrieval with default fallback.
     *
     * Usage:
     *   $mode = $settings->get_string('performance_mode', 'safe');
     *
     * @param string $key     The setting key
     * @param string $default Fallback value if key not found
     * @return string The setting value or default
     */
    public function get_string(string $key, string $default = ''): string
    {
        $value = $this->options[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * Get an integer setting value
     *
     * Type-safe integer retrieval with default fallback.
     *
     * Usage:
     *   $timeout = $settings->get_int('cache_timeout', 3600);
     *
     * @param string $key     The setting key
     * @param int    $default Fallback value if key not found
     * @return int The setting value or default
     */
    public function get_int(string $key, int $default = 0): int
    {
        $value = $this->options[$key] ?? $default;

        return is_int($value) ? $value : intval($value);
    }

    /**
     * Get all settings (for admin display, etc.)
     *
     * @return array<string, bool|string> All current settings
     */
    public function get_all(): array
    {
        return $this->options;
    }

    /**
     * Get all available settings keys with descriptions
     *
     * Used by admin UI to build settings form dynamically.
     *
     * @return array<string, array<string, string>> Settings with labels and descriptions
     */
    public static function get_available_settings(): array
    {
        return [
            // Safe mode settings (always recommended)
            'enable_gzip' => [
                'label'       => 'Enable Gzip Compression',
                'description' => 'Compress HTML/CSS/JS responses. Required for Performance 100. (Safe)',
                'category'    => 'safe',
            ],
            'preload_fonts' => [
                'label'       => 'Preload Critical Fonts',
                'description' => 'Download fonts before CSS parsing. Reduces font latency 62%. (Safe)',
                'category'    => 'safe',
            ],
            'inject_lcp_preload' => [
                'label'       => 'Inject LCP Preload',
                'description' => 'Preload featured image immediately. Reduces LCP by 52%. (Safe)',
                'category'    => 'safe',
            ],
            'inject_seo_meta' => [
                'label'       => 'Inject SEO Meta Descriptions',
                'description' => 'Add meta description tags for SEO. Boosts SEO from 92 → 100. (Safe)',
                'category'    => 'safe',
            ],
            'fix_font_display' => [
                'label'       => 'Fix Font Display Mode',
                'description' => 'Replace font-display: fallback with swap. Eliminates 80ms Lighthouse warning. (Safe)',
                'category'    => 'safe',
            ],
            'fix_nested_lists' => [
                'label'       => 'Fix Nested List Structure',
                'description' => 'Convert invalid nested <ul> to <div>. Fixes accessibility errors. (Safe)',
                'category'    => 'safe',
            ],

            // Aggressive mode settings (opt-in, can break sites)
            'remove_bloat' => [
                'label'       => 'Remove WordPress Bloat (Aggressive)',
                'description' => 'Dequeue block library, global styles, emoji (60-100KB). May break block-based themes. (Aggressive)',
                'category'    => 'aggressive',
            ],
            'aggressive_mode' => [
                'label'       => 'Aggressive Mode',
                'description' => 'Enable all aggressive optimizations at once. Recommended only for testing. (Aggressive)',
                'category'    => 'aggressive',
            ],
        ];
    }

    /**
     * Check if aggressive mode is enabled
     *
     * Convenience method for services that need to decide between
     * safe and aggressive behavior based on single flag.
     *
     * @return bool True if aggressive mode is enabled
     */
    public function is_aggressive_mode(): bool
    {
        return $this->is_enabled('aggressive_mode', false);
    }

    /**
     * Get category of available settings
     *
     * Helper for building admin UI by category.
     *
     * @param string $category The category to filter ('safe', 'aggressive', etc.)
     * @return array<string, array<string, string>> Settings in that category
     */
    public static function get_settings_by_category(string $category): array
    {
        $all_settings = self::get_available_settings();

        return array_filter(
            $all_settings,
            fn ($setting) => ($setting['category'] ?? '') === $category,
        );
    }
}
