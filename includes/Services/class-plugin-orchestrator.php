<?php

declare(strict_types=1);

/**
 * Plugin Orchestrator
 *
 * The "Brain" of the plugin. Coordinates all services using the
 * Service-Oriented Architecture pattern.
 *
 * Instead of the main plugin file calling 13 different hooks directly,
 * this orchestrator initializes specific services. Each service is:
 * - Single Responsibility (one job)
 * - Independent (can be swapped/extended without breaking others)
 * - Testable (can test in isolation)
 *
 * Follows Dependency Inversion Principle:
 * High-level policy (orchestrator) doesn't depend on low-level details (services).
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * ODR_Optimizer: Service Orchestrator
 *
 * Initializes the 4-service architecture:
 * 1. Server_Service: HTTP transport optimization (gzip, headers)
 * 2. Asset_Service: Critical path optimization (fonts, bloat removal)
 * 3. Image_Service: LCP optimization (preloading featured images)
 * 4. Compatibility_Service: Theme-specific fixes (HTML sanitization, SEO)
 *
 * This design ensures:
 * - Adding "Database Optimization" or "CDN Support" later doesn't break existing logic
 * - Each service can be tested independently
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
     * @return void
     */
    public function init(): void
    {
        // 1. Optimize the HTTP transport layer (gzip, headers)
        (new Server_Service())->register();

        // 2. Optimize critical rendering path (fonts, bloat removal)
        (new Asset_Service())->register();

        // 3. Optimize Largest Contentful Paint (LCP)
        (new Image_Service())->register();

        // 4. Fix theme-specific HTML issues (sanitization, SEO)
        (new Compatibility_Service())->register();
    }
}
