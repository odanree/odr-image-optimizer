<?php

declare(strict_types=1);

/**
 * Plugin Orchestrator
 *
 * The "Brain" of the plugin. Coordinates all services using the
 * Service-Oriented Architecture pattern with Dependency Injection.
 *
 * Instead of the main plugin file calling 13 different hooks directly,
 * this orchestrator initializes specific services. Each service is:
 * - Single Responsibility (one job)
 * - Independent (can be swapped/extended without breaking others)
 * - Testable (can test in isolation)
 * - Dependency-injected (receives dependencies, doesn't create them)
 *
 * Follows Dependency Inversion Principle:
 * High-level policy (orchestrator) doesn't depend on low-level details (services).
 * Services depend on abstractions (Settings_Repository) not on WordPress directly.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

use ImageOptimizer\Admin\Admin_Settings;

/**
 * Plugin_Orchestrator: Service Orchestrator with Dependency Injection
 *
 * Initializes the 5-component architecture:
 * 1. Settings_Repository: Configuration access (no direct get_option calls)
 * 2. Server_Service: HTTP transport optimization (gzip, headers)
 * 3. Asset_Service: Critical path optimization (fonts, bloat removal)
 * 4. Image_Service: LCP optimization (preloading featured images)
 * 5. Compatibility_Service: Theme-specific fixes (HTML sanitization, SEO)
 * 6. Admin_Settings: WordPress admin UI for settings
 *
 * This design ensures:
 * - Adding "Database Optimization" or "CDN Support" later doesn't break existing logic
 * - Each service can be tested independently
 * - Services are "smart" but don't know about WordPress internals
 * - Settings are centralized and easy to modify
 * - New team members can understand the architecture quickly
 * - The main plugin file stays clean and readable
 */
class Plugin_Orchestrator
{
    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Settings repository (shared across all services)
     *
     * @var Settings_Repository|null
     */
    private ?Settings_Repository $settings = null;

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize all services
     *
     * This is the single entry point for the entire plugin.
     * All optimization logic flows from here through individual services.
     *
     * Dependency Injection Flow:
     * 1. Create Settings_Repository (shared across services)
     * 2. Pass settings to each service that needs configuration
     * 3. Services call register() to hook into WordPress
     * 4. Admin UI is initialized separately for settings management
     *
     * @return void
     */
    public function init(): void
    {
        // Create the shared settings repository
        // This is instantiated once and passed to all services
        $this->settings = new Settings_Repository();

        // 1. Initialize Admin Settings UI (runs on admin_menu, admin_init)
        (new Admin_Settings($this->settings))->register();

        // 2. Apply cache headers for long-term asset caching
        // Must run early (template_redirect) before content output
        $header_manager = new HeaderManager();
        add_action('template_redirect', [$header_manager, 'apply_cache_headers'], 1);

        // 3. Clean up bloat and defer non-critical scripts
        // Runs at wp_enqueue_scripts to remove/defer unnecessary resources
        $cleanup_service = new CleanupService();
        add_action('wp_enqueue_scripts', [$cleanup_service, 'remove_bloat'], 100);

        // 4. Optimize the HTTP transport layer (gzip, headers)
        // Only runs if enabled in settings
        if ($this->settings->is_enabled('enable_gzip')) {
            (new Server_Service())->register();
        }

        // 5. Optimize critical rendering path (fonts, bloat removal)
        // Injects settings so it can respect user preferences
        (new Asset_Service($this->settings))->register();

        // 6. Optimize Largest Contentful Paint (LCP)
        // Only runs if enabled in settings
        if ($this->settings->is_enabled('inject_lcp_preload')) {
            (new Image_Service())->register();
        }

        // 7. Fix theme-specific HTML issues (sanitization, SEO)
        // Injects settings so it respects user's aggressive mode choice
        (new Compatibility_Service($this->settings))->register();
    }

    /**
     * Get the settings repository
     *
     * Used by services or external code that needs access to settings.
     *
     * @return Settings_Repository The shared settings repository
     */
    public function get_settings(): Settings_Repository
    {
        if ($this->settings === null) {
            $this->settings = new Settings_Repository();
        }

        return $this->settings;
    }
}
