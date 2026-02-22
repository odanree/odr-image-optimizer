<?php

declare(strict_types=1);

/**
 * Asset Service
 *
 * Handles critical rendering path optimizations by managing fonts and bloat removal.
 * Focuses on eliminating render-blocking resources and non-essential CSS/JS.
 *
 * Single Responsibility: Critical Path & Font Management
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Asset_Service: Optimize critical rendering path
 *
 * Isolates font and asset logic from other optimizations.
 * If you later add CDN support or Database optimization, this stays clean.
 */
class Asset_Service
{
    /**
     * Register hooks for asset optimizations
     *
     * @return void
     */
    public function register(): void
    {
        // Preload critical fonts early (priority 0 = before everything)
        add_action('wp_head', [$this, 'preload_critical_fonts'], 0);

        // Remove WordPress bloat late (priority 999 = after all plugins/themes enqueue)
        add_action('wp_enqueue_scripts', [$this, 'remove_core_bloat'], 999);
    }

    /**
     * Preload critical fonts at ultra-high priority
     *
     * This runs BEFORE priority 1, ensuring fonts start downloading
     * before CSS parsing. Solves the font latency chain:
     * HTML → CSS parse → @font-face discovery → font download
     *
     * With preload: HTML + Font download happen in parallel with CSS parse
     *
     * Example output:
     * <link rel="preload" href="..." as="font" type="font/woff2" crossorigin>
     *
     * @return void
     */
    public function preload_critical_fonts(): void
    {
        if (is_admin()) {
            return;
        }

        // Get theme's primary font URL (e.g., from theme customizer or constants)
        $font_url = $this->get_theme_font_url();

        if (empty($font_url)) {
            return;
        }

        // Output preload link for critical font
        echo '<link rel="preload" href="' . esc_url($font_url) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }

    /**
     * Remove WordPress core bloat
     *
     * Dequeues non-essential CSS and JavaScript that theme doesn't need.
     * Common items:
     * - wp-block-library (Gutenberg block styles)
     * - global-styles (global styles when not needed)
     * - Classic theme CSS
     *
     * Runs at priority 999 (extremely late) to ensure all plugins/themes
     * have already enqueued their scripts before we dequeue.
     *
     * @return void
     */
    public function remove_core_bloat(): void
    {
        if (is_admin()) {
            return;
        }

        // Remove block library styles if not using Gutenberg blocks
        wp_dequeue_style('wp-block-library');

        // Remove global styles if theme provides its own
        wp_dequeue_style('global-styles');

        // Remove emoji styles if not needed
        wp_dequeue_style('print-emoji-styles');
        wp_dequeue_script('wp-emoji');

        // Remove classic theme styles if using block-based theme
        wp_dequeue_style('classic-theme-styles');
    }

    /**
     * Get the theme's primary font URL
     *
     * This is a helper method to retrieve the font URL from various sources.
     * Can be extended to check theme customizer, constants, etc.
     *
     * @return string Font URL or empty string if not found
     */
    private function get_theme_font_url(): string
    {
        // Check if a font URL constant is defined
        if (defined('ODR_THEME_FONT_URL')) {
            return ODR_THEME_FONT_URL;
        }

        // Check if theme declares a fonts_url function
        if (function_exists('get_theme_mod')) {
            $font_url = get_theme_mod('primary_font_url', '');
            if (! empty($font_url)) {
                return $font_url;
            }
        }

        // Default: empty (no critical font to preload)
        return '';
    }
}
