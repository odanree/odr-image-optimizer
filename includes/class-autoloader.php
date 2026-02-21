<?php

declare(strict_types=1);
/**
 * Autoloader for Image Optimizer classes
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer;

/**
 * Autoloader class
 */
class Autoloader
{
    /**
     * Register the autoloader
     */
    public static function register()
    {
        spl_autoload_register([ __CLASS__, 'autoload' ]);
    }

    /**
     * Autoload function
     *
     * @param string $class The class name.
     */
    public static function autoload($class)
    {
        // Only autoload ImageOptimizer classes
        if (0 !== strpos($class, 'ImageOptimizer')) {
            return;
        }

        // Remove the namespace prefix
        $class_name = str_replace('ImageOptimizer\\', '', $class);

        // Convert namespace to file path with kebab-case for class names
        $parts = explode('\\', $class_name);
        $file_name = 'class-' . strtolower(str_replace('_', '-', array_pop($parts))) . '.php';

        // Build directory path from remaining namespace parts (keep original case for compatibility with case-sensitive filesystems)
        $dir_path = '';
        if (! empty($parts)) {
            $dir_path = implode('/', $parts) . '/';
        }

        // Construct full file path
        $file = IMAGE_OPTIMIZER_PATH . 'includes/' . $dir_path . $file_name;

        // Load the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
