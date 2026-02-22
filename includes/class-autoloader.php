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
        $class_part = array_pop($parts);

        // Convert CamelCase and snake_case to kebab-case
        $kebab_class = self::to_kebab_case($class_part);
        $file_name = 'class-' . $kebab_class . '.php';

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

    /**
     * Convert CamelCase and snake_case to kebab-case
     *
     * @param string $string The string to convert.
     * @return string The kebab-cased string.
     */
    private static function to_kebab_case($string)
    {
        // First, replace underscores with hyphens (for snake_case)
        $string = str_replace('_', '-', $string);

        // Then, insert hyphens before uppercase letters (for CamelCase)
        // and convert to lowercase
        $string = preg_replace('/([a-z])([A-Z])/', '$1-$2', $string);
        $string = strtolower($string);

        return $string;
    }
}
