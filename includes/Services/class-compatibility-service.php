<?php

declare(strict_types=1);

/**
 * Compatibility Service
 *
 * Handles theme-specific fixes and workarounds.
 * This is where "hacks" go - acknowledged as theme-specific, not core plugin features.
 * Includes output buffer fixes (font-display, nested lists) and SEO meta tags.
 *
 * Single Responsibility: HTML Compatibility & SEO
 * Dependency Injection: Receives Settings_Repository to respect user preferences.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Compatibility_Service: Fix theme-specific HTML issues
 *
 * Applies regex-based output buffer fixes for:
 * 1. Font-display: fallback → swap (Lighthouse warning elimination)
 * 2. Nested <ul> elements (accessibility violation fix)
 * 3. SEO meta descriptions (missing on homepage/archives)
 *
 * By isolating these here, we acknowledge they're workarounds for theme issues,
 * not core plugin features.
 *
 * Respects user settings via dependency injection.
 */
class Compatibility_Service
{
    /**
     * Settings repository for accessing plugin configuration
     *
     * @var Settings_Repository
     */
    private Settings_Repository $settings;

    /**
     * Constructor: Inject settings repository
     *
     * @param Settings_Repository $settings The settings repository instance
     */
    public function __construct(Settings_Repository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Register hooks for compatibility fixes
     *
     * Only registers hooks for enabled features.
     *
     * @return void
     */
    public function register(): void
    {
        // Start output buffer to fix HTML issues (priority 2 = early)
        // Runs if font-display or nested list fixes are enabled
        if ($this->settings->is_enabled('fix_font_display') || $this->settings->is_enabled('fix_nested_lists')) {
            add_action('template_redirect', [$this, 'start_output_buffer'], 2);
        }

        // Inject SEO meta tags in wp_head
        // Only if SEO meta injection is enabled
        if ($this->settings->is_enabled('inject_seo_meta')) {
            add_action('wp_head', [$this, 'inject_seo_meta'], 1);
        }

        // Clean up list structures for accessibility
        add_filter('wp_nav_menu', [$this, 'sanitize_nav_lists']);
        add_filter('widget_display_callback', [$this, 'sanitize_widget_lists'], 10, 3);
    }

    /**
     * Start output buffer to sanitize HTML
     *
     * This buffer catches the entire HTML before sending to browser.
     * Allows us to fix:
     * 1. font-display: fallback → swap (in <head> only)
     * 2. Nested <ul> elements (in <body> only)
     *
     * By splitting head/body, we target fixes precisely.
     *
     * @return void
     */
    public function start_output_buffer(): void
    {
        if (is_admin()) {
            return;
        }

        ob_start([$this, 'sanitize_output']);
    }

    /**
     * Sanitize HTML output buffer
     *
     * Fixes:
     * 1. font-display: fallback/optional → swap in <head>
     * 2. Nested <ul> elements in <body> (WordPress Navigation blocks)
     *
     * @param string $buffer The output buffer content
     * @return string Modified buffer
     */
    public function sanitize_output(string $buffer): string
    {
        if (empty($buffer)) {
            return $buffer;
        }

        // Find head and body sections
        $head_end = strpos($buffer, '</head>');
        $body_start = strpos($buffer, '<body');

        if (false === $head_end || false === $body_start) {
            return $buffer; // No </head> or <body> found, return unmodified
        }

        // Split buffer into sections
        $head = substr($buffer, 0, $head_end);
        $body_start_position = strpos($buffer, '>', $body_start);

        if (false === $body_start_position) {
            return $buffer; // Malformed HTML, return unmodified
        }

        $body_section_start = $body_start_position + 1;
        $middle = substr($buffer, $head_end, $body_section_start - $head_end);
        $body = substr($buffer, $body_section_start);

        // ===== FIX 1: Font-display in head =====
        // Replace font-display: fallback/optional with swap ONLY in <head>
        $head = str_replace('font-display: fallback', 'font-display: swap', $head);
        $head = str_replace('font-display:fallback', 'font-display: swap', $head);
        $head = str_replace('font-display: optional', 'font-display: swap', $head);
        $head = str_replace('font-display:optional', 'font-display: swap', $head);

        // ===== FIX 2: Nested <ul> elements in body =====
        // WordPress Navigation blocks render: <ul><ul> without wrapping
        // This violates accessibility (lists should only have <li> children)
        // Solution: Replace outer <ul> with <div> to hold the nested <ul>

        $body = (string) preg_replace(
            '/<ul\s+class="wp-block-navigation__container[^"]*">\s*<ul\s+class="wp-block-page-list"/',
            '<div class="wp-block-navigation__container-wrapper"><ul class="wp-block-page-list"',
            $body,
        );

        // Close the wrapper div where the outer </ul> was
        $body = (string) preg_replace(
            '/<\/ul>\s*<\/ul>(\s*<\/)/U',
            '</ul></div>$1',
            $body,
        );

        return $head . $middle . $body;
    }

    /**
     * Inject SEO meta description tag
     *
     * Adds <meta name="description"> tag for:
     * - Singular posts/pages (uses excerpt)
     * - Homepage (uses site tagline)
     * - Archives (uses archive title)
     *
     * This resolves Lighthouse SEO audit: "Document has a meta description"
     * Boosts SEO score from 92 → 100.
     *
     * @return void
     */
    public function inject_seo_meta(): void
    {
        if (is_admin()) {
            return;
        }

        // Determine meta description based on page type
        $desc = '';

        if (is_singular()) {
            // Singular post/page: use excerpt if available
            $excerpt = get_the_excerpt();
            $desc = (string) ($excerpt ?: 'High-performance image suite optimized for 100/100 Lighthouse scores.');
        } elseif (is_home() || is_front_page()) {
            // Homepage: use site tagline or description
            $bloginfo = get_bloginfo('description');
            $blogname = get_bloginfo('name');
            $desc = (string) ($bloginfo ?: ($blogname ?: 'High-performance image suite optimized for 100/100 Lighthouse scores.'));
        } elseif (is_archive()) {
            // Archive pages: use archive title
            $archive_title = get_the_archive_title();
            $desc = (string) ($archive_title ?: 'Archive page');
        } else {
            // Fallback for other page types
            $desc = 'High-performance image suite optimized for 100/100 Lighthouse scores.';
        }

        // Output the meta description tag (properly escaped)
        if (! empty($desc)) {
            echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($desc)) . '">' . "\n";
        }
    }

    /**
     * Sanitize navigation menu lists
     *
     * Removes stray <div> wrappers inside <ul> tags that themes add for styling.
     * Lighthouse flags nested divs in lists as accessibility violations.
     *
     * Pattern: <ul><div>content</div></ul> → <ul>content</ul>
     *
     * @param string $nav_menu The navigation menu HTML
     * @return string Sanitized menu HTML
     */
    public function sanitize_nav_lists(string $nav_menu): string
    {
        if (is_admin()) {
            return $nav_menu;
        }

        // Remove <div> wrappers inside <ul> tags
        return (string) preg_replace('/<ul([^>]*)>\s*<div[^>]*>(.*)<\/div>\s*<\/ul>/isU', '<ul$1>$2</ul>', $nav_menu);
    }

    /**
     * Sanitize widget lists
     *
     * Ensures widget lists don't have stray <div> elements inside <ul> tags.
     *
     * @param mixed $instance The widget instance settings
     * @param object $widget   The widget object
     * @param array<string, mixed>  $args     The display arguments
     * @return mixed Modified widget instance
     */
    public function sanitize_widget_lists($instance, $widget, $args)
    {
        if (is_admin() || ! is_array($args)) {
            return $instance;
        }

        // Remove stray <div> from widget wrapper if wrapping a list
        if (isset($args['before_widget']) && is_string($args['before_widget'])) {
            $args['before_widget'] = str_replace('<div>', '', $args['before_widget']);
        }

        return $instance;
    }
}
