<?php

declare(strict_types=1);

/**
 * Dependency Injection Factory
 *
 * Provides instances of optimizer and other services following Dependency Inversion.
 * This keeps the plugin architecture clean and testable.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Service container for dependency injection
 */
class Container
{
    /**
     * Cached instances
     *
     * @var array<string, object>
     */
    private static $instances = [];

    /**
     * Get or create an Optimizer instance
     *
     * @param ToolRegistry|null      $tool_registry Optional tool registry for dependency injection.
     * @param OptimizerConfig|null   $config Optional configuration for dependency injection.
     * @return Optimizer The optimizer service.
     */
    public static function get_optimizer(?ToolRegistry $tool_registry = null, ?OptimizerConfig $config = null): Optimizer
    {
        if (! isset(self::$instances['optimizer'])) {
            // If no tool registry provided, create one
            if (! $tool_registry) {
                $tool_registry = self::get_tool_registry();
            }
            
            // If no config provided, load from WordPress options
            if (! $config) {
                $config = OptimizerConfig::from_wordpress_options();
            }
            
            self::$instances['optimizer'] = new Optimizer($tool_registry, $config);
        }
        return self::$instances['optimizer'];
    }

    /**
     * Get or create a ToolRegistry instance
     *
     * @return ToolRegistry The tool registry service.
     */
    public static function get_tool_registry(): ToolRegistry
    {
        if (! isset(self::$instances['tool_registry'])) {
            self::$instances['tool_registry'] = new ToolRegistry();
        }
        return self::$instances['tool_registry'];
    }

    /**
     * Get or create a PermissionsManager instance
     *
     * @return PermissionsManager The permissions manager service.
     */
    public static function get_permissions_manager(): PermissionsManager
    {
        if (! isset(self::$instances['permissions_manager'])) {
            self::$instances['permissions_manager'] = new PermissionsManager();
        }
        return self::$instances['permissions_manager'];
    }

    /**
     * Set a custom instance (for testing or overrides)
     *
     * @param string $service The service name.
     * @param object $instance The service instance.
     */
    public static function set_instance(string $service, object $instance): void
    {
        self::$instances[$service] = $instance;
    }

    /**
     * Clear all instances (for testing)
     */
    public static function clear(): void
    {
        self::$instances = [];
    }
}
