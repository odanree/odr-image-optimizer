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

use ImageOptimizer\Adapter\WordPressAdapter;

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
     * Get or create an ImageResizer instance
     *
     * @param ToolRegistry|null   $tool_registry Optional tool registry.
     * @param ResizingConfig|null $config Optional resizing configuration.
     * @return ImageResizer The image resizer service.
     */
    public static function get_image_resizer(?ToolRegistry $tool_registry = null, ?ResizingConfig $config = null): ImageResizer
    {
        if (! isset(self::$instances['image_resizer'])) {
            if (! $tool_registry) {
                $tool_registry = self::get_tool_registry();
            }
            if (! $config) {
                $config = ResizingConfig::from_wordpress_options();
            }
            self::$instances['image_resizer'] = new ImageResizer($tool_registry, $config);
        }
        return self::$instances['image_resizer'];
    }

    /**
     * Get or create a ResizingProcessor instance
     *
     * @param ImageResizer|null $resizer Optional image resizer.
     * @return ResizingProcessor The resizing processor service.
     */
    public static function get_resizing_processor(?ImageResizer $resizer = null): ResizingProcessor
    {
        if (! isset(self::$instances['resizing_processor'])) {
            if (! $resizer) {
                $resizer = self::get_image_resizer();
            }
            self::$instances['resizing_processor'] = new ResizingProcessor($resizer);
        }
        return self::$instances['resizing_processor'];
    }

    /**
     * Get or create a WordPressAdapter instance
     *
     * @return WordPressAdapter The WordPress adapter service.
     */
    public static function get_wordpress_adapter(): WordPressAdapter
    {
        if (! isset(self::$instances['wordpress_adapter'])) {
            self::$instances['wordpress_adapter'] = new WordPressAdapter();
        }
        return self::$instances['wordpress_adapter'];
    }

    /**
     * Get or create a PriorityService instance
     *
     * @return \ImageOptimizer\Services\PriorityService The priority service.
     */
    public static function get_priority_service(): \ImageOptimizer\Services\PriorityService
    {
        if (! isset(self::$instances['priority_service'])) {
            self::$instances['priority_service'] = new \ImageOptimizer\Services\PriorityService();
        }
        return self::$instances['priority_service'];
    }

    /**
     * Get or create an AssetManager instance
     *
     * @return \ImageOptimizer\Services\AssetManager The asset manager service.
     */
    public static function get_asset_manager(): \ImageOptimizer\Services\AssetManager
    {
        if (! isset(self::$instances['asset_manager'])) {
            self::$instances['asset_manager'] = new \ImageOptimizer\Services\AssetManager();
        }
        return self::$instances['asset_manager'];
    }

    /**
     * Get or create a CleanupService instance
     *
     * @return \ImageOptimizer\Services\CleanupService The cleanup service.
     */
    public static function get_cleanup_service(): \ImageOptimizer\Services\CleanupService
    {
        if (! isset(self::$instances['cleanup_service'])) {
            self::$instances['cleanup_service'] = new \ImageOptimizer\Services\CleanupService();
        }
        return self::$instances['cleanup_service'];
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
