<?php

declare(strict_types=1);

/**
 * Tool Registry for External Image Processing Tools
 *
 * Manages paths and configurations for external tools like cwebp, jpegoptim, etc.
 * Allows dependency injection of tool paths instead of hardcoding them.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Tool registry for managing external tool configurations
 */
class ToolRegistry
{
    /**
     * Registered tools and their paths
     *
     * @var array<string, string>
     */
    private $tools = [];

    /**
     * Constructor
     *
     * Auto-detect available tools on the system.
     */
    public function __construct()
    {
        $this->auto_detect_tools();
    }

    /**
     * Auto-detect available system tools
     *
     * Checks common locations for image optimization tools.
     */
    private function auto_detect_tools(): void
    {
        // cwebp - WebP conversion
        $cwebp = $this->find_executable('cwebp');
        if ($cwebp) {
            $this->tools['cwebp'] = $cwebp;
        }

        // jpegoptim - JPEG optimization
        $jpegoptim = $this->find_executable('jpegoptim');
        if ($jpegoptim) {
            $this->tools['jpegoptim'] = $jpegoptim;
        }

        // optipng - PNG optimization
        $optipng = $this->find_executable('optipng');
        if ($optipng) {
            $this->tools['optipng'] = $optipng;
        }

        // ImageMagick (convert command)
        $convert = $this->find_executable('convert');
        if ($convert) {
            $this->tools['imagemagick'] = $convert;
        }

        // ImageMagick (magick command - newer)
        $magick = $this->find_executable('magick');
        if ($magick) {
            $this->tools['imagemagick'] = $magick;
        }
    }

    /**
     * Find executable in system PATH
     *
     * @param string $executable The executable name.
     * @return string|null The full path to the executable, or null if not found.
     */
    private function find_executable(?string $executable): ?string
    {
        if (! $executable) {
            return null;
        }

        // Try using which command (Unix-like systems)
        if (function_exists('shell_exec')) {
            $path = shell_exec("which $executable 2>/dev/null");
            if ($path) {
                return trim($path);
            }
        }

        // Try common locations
        $common_paths = [
            "/usr/bin/$executable",
            "/usr/local/bin/$executable",
            "/opt/local/bin/$executable",
            "/sw/bin/$executable",
        ];

        foreach ($common_paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Register a tool path manually
     *
     * Allows overriding auto-detected tools or registering custom ones.
     *
     * @param string $tool_name The tool name (e.g., 'cwebp').
     * @param string $path The full path to the executable.
     *
     * @throws \InvalidArgumentException If path doesn't exist or isn't executable.
     */
    public function register(string $tool_name, string $path): void
    {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Tool not found at: $path");
        }

        if (! is_executable($path)) {
            throw new \InvalidArgumentException("Tool is not executable: $path");
        }

        $this->tools[$tool_name] = $path;
    }

    /**
     * Get tool path
     *
     * @param string $tool_name The tool name.
     * @return string|null The path, or null if not available.
     */
    public function get(string $tool_name): ?string
    {
        return $this->tools[$tool_name] ?? null;
    }

    /**
     * Check if tool is available
     *
     * @param string $tool_name The tool name.
     * @return bool
     */
    public function has(string $tool_name): bool
    {
        return isset($this->tools[$tool_name]);
    }

    /**
     * Get all registered tools
     *
     * @return array<string, string> Map of tool names to paths.
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Clear all tools (useful for testing)
     */
    public function clear(): void
    {
        $this->tools = [];
    }

    /**
     * Get registry as array suitable for dependency injection
     *
     * @return array Configuration array for constructor injection.
     */
    public function to_array(): array
    {
        return $this->tools;
    }
}
