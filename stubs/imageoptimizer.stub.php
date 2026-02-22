<?php
/**
 * ImageOptimizer Plugin Stubs for PHPStan Analysis
 *
 * Class stubs for new Service-Oriented Architecture classes.
 */

namespace ImageOptimizer\Services {
    /**
     * Settings Repository for configuration access
     *
     * Single source of truth for all plugin configuration.
     * Services depend on this abstraction, not WordPress directly.
     */
    class Settings_Repository {
        /**
         * Check if a feature is enabled
         *
         * @param string $key
         * @param bool $default
         * @return bool
         */
        public function is_enabled(string $key, bool $default = false): bool {}

        /**
         * Get a string setting value
         *
         * @param string $key
         * @param string $default
         * @return string
         */
        public function get_string(string $key, string $default = ''): string {}

        /**
         * Get an integer setting value
         *
         * @param string $key
         * @param int $default
         * @return int
         */
        public function get_int(string $key, int $default = 0): int {}

        /**
         * Get all settings
         *
         * @return array<string, mixed>
         */
        public function get_all(): array {}

        /**
         * Check if aggressive mode is enabled
         *
         * @return bool
         */
        public function is_aggressive_mode(): bool {}

        /**
         * Get all available settings with metadata
         *
         * @return array<string, array<string, mixed>>
         */
        public static function get_available_settings(): array {}

        /**
         * Get settings filtered by category
         *
         * @param string $category
         * @return array<string, array<string, mixed>>
         */
        public static function get_settings_by_category(string $category): array {}
    }
}

namespace ImageOptimizer\Admin {
    /**
     * Admin Settings UI
     *
     * Handles the WordPress admin menu and settings form rendering.
     * Single Responsibility: Display and save settings in WordPress admin.
     */
    class Admin_Settings {
        /**
         * Constructor with dependency injection
         *
         * @param \ImageOptimizer\Services\Settings_Repository $settings
         */
        public function __construct(\ImageOptimizer\Services\Settings_Repository $settings) {}

        /**
         * Register settings and hooks
         *
         * @return void
         */
        public function register(): void {}
    }
}
