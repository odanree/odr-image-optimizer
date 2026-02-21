<?php

declare(strict_types=1);

/**
 * Settings Policy
 *
 * Defines what services are *allowed* to do based on plugin settings.
 * Services depend on this policy, NOT on where settings are stored.
 * This follows the Dependency Inversion Principle (DIP).
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Settings policy - defines permissions for services
 *
 * Manages two policy domains:
 * 1. Media Policy (file-system operations)
 * 2. Delivery Policy (frontend HTML/rendering)
 *
 * Services call these methods to check if they're allowed to execute.
 * This decouples services from WordPress option storage.
 *
 * @phpstan-type SettingsShape array{
 *   compression_level: 'low'|'medium'|'high',
 *   enable_webp: bool,
 *   lazy_load_mode: 'native'|'hybrid'|'off',
 *   auto_optimize: bool,
 *   preload_fonts: bool,
 *   kill_bloat: bool,
 *   inline_critical_css: bool
 * }
 */
class SettingsPolicy
{
    /**
     * Option name (global namespace)
     *
     * @var string
     */
    private const OPTION_NAME = 'odr_image_optimizer_settings';

    /**
     * Get the complete settings object with safe defaults
     *
     * Defaults to TRUE for optimizations (Lighthouse mode).
     * Settings are only disabled if explicitly set to false.
     *
     * @return array{compression_level: 'low'|'medium'|'high', enable_webp: bool, lazy_load_mode: 'native'|'hybrid'|'off', auto_optimize: bool, preload_fonts: bool, kill_bloat: bool, inline_critical_css: bool}
     */
    public static function get_settings(): array
    {
        $raw = get_option(self::OPTION_NAME, []);

        return [
            'compression_level'   => self::sanitize_compression_level($raw['compression_level'] ?? 'medium'),
            'enable_webp'         => ! isset($raw['enable_webp']) || ! empty($raw['enable_webp']),
            'lazy_load_mode'      => self::sanitize_lazy_mode($raw['lazy_load_mode'] ?? 'native'),
            'auto_optimize'       => ! isset($raw['auto_optimize']) || ! empty($raw['auto_optimize']),
            'preload_fonts'       => ! isset($raw['preload_fonts']) || ! empty($raw['preload_fonts']),
            'kill_bloat'          => ! isset($raw['kill_bloat']) || ! empty($raw['kill_bloat']),
            'inline_critical_css' => ! isset($raw['inline_critical_css']) || ! empty($raw['inline_critical_css']),
        ];
    }

    /**
     * Media Policy: What operations are allowed on the file system?
     *
     * @return array{webp_enabled: bool, compression_level: 'low'|'medium'|'high', auto_optimize: bool}
     */
    public static function get_media_policy(): array
    {
        $settings = self::get_settings();

        return [
            'webp_enabled'       => $settings['enable_webp'],
            'compression_level'  => $settings['compression_level'],
            'auto_optimize'      => $settings['auto_optimize'],
        ];
    }

    /**
     * Delivery Policy: What operations are allowed on frontend rendering?
     *
     * @return array{lazy_load_mode: 'native'|'hybrid'|'off', preload_fonts: bool, remove_bloat: bool, inline_css: bool}
     */
    public static function get_delivery_policy(): array
    {
        $settings = self::get_settings();

        return [
            'lazy_load_mode'  => $settings['lazy_load_mode'],
            'preload_fonts'   => $settings['preload_fonts'],
            'remove_bloat'    => $settings['kill_bloat'],
            'inline_css'      => $settings['inline_critical_css'],
        ];
    }

    /**
     * Check if WebP conversion is allowed
     *
     * @return bool
     */
    public static function can_convert_webp(): bool
    {
        return self::get_media_policy()['webp_enabled'];
    }

    /**
     * Check if auto-optimization on upload is allowed
     *
     * @return bool
     */
    public static function should_auto_optimize(): bool
    {
        return self::get_media_policy()['auto_optimize'];
    }

    /**
     * Check if font preloading is allowed
     *
     * @return bool
     */
    public static function should_preload_fonts(): bool
    {
        return self::get_delivery_policy()['preload_fonts'];
    }

    /**
     * Check if bloat removal is allowed
     *
     * @return bool
     */
    public static function should_remove_bloat(): bool
    {
        return self::get_delivery_policy()['remove_bloat'];
    }

    /**
     * Check if inline critical CSS is allowed
     *
     * @return bool
     */
    public static function should_inline_critical_css(): bool
    {
        return self::get_delivery_policy()['inline_css'];
    }

    /**
     * Get the lazy loading mode
     *
     * @return 'native'|'hybrid'|'off'
     */
    public static function get_lazy_load_mode(): string
    {
        return self::get_delivery_policy()['lazy_load_mode'];
    }

    /**
     * Get compression level
     *
     * @return 'low'|'medium'|'high'
     */
    public static function get_compression_level(): string
    {
        return self::get_media_policy()['compression_level'];
    }

    /**
     * Sanitize compression level
     *
     * @param mixed $value Raw value to sanitize.
     * @return 'low'|'medium'|'high'
     */
    private static function sanitize_compression_level($value): string
    {
        $allowed = [ 'low', 'medium', 'high' ];

        return in_array($value, $allowed, true) ? $value : 'medium';
    }

    /**
     * Sanitize lazy load mode
     *
     * @param mixed $value Raw value to sanitize.
     * @return 'native'|'hybrid'|'off'
     */
    private static function sanitize_lazy_mode($value): string
    {
        $allowed = [ 'native', 'hybrid', 'off' ];

        return in_array($value, $allowed, true) ? $value : 'native';
    }
}
