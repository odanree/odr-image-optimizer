<?php

declare(strict_types=1);

/**
 * Resizing Configuration Value Object
 *
 * Encapsulates image resizing settings.
 * Follows Dependency Inversion: Config is injected, not fetched internally.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Immutable configuration for image resizing
 */
class ResizingConfig
{
    /**
     * Maximum image width (pixels)
     *
     * @var int
     */
    private $max_image_width;

    /**
     * Whether to resize on upload
     *
     * @var bool
     */
    private $resize_on_upload;

    /**
     * Constructor (private - use factory methods)
     *
     * @param int  $max_image_width Maximum width in pixels.
     * @param bool $resize_on_upload Resize on upload.
     */
    private function __construct(int $max_image_width = 1920, bool $resize_on_upload = true)
    {
        $this->max_image_width = max(300, min(4096, $max_image_width));  // Constrain: 300-4096px
        $this->resize_on_upload = $resize_on_upload;
    }

    /**
     * Create from WordPress options
     *
     * @return self Configuration instance.
     */
    public static function from_wordpress_options(): self
    {
        $settings = get_option('image_optimizer_settings', []);

        return new self(
            $settings['max_image_width'] ?? 1920,
            $settings['resize_on_upload'] ?? true
        );
    }

    /**
     * Create default configuration
     *
     * @return self Configuration instance.
     */
    public static function defaults(): self
    {
        return new self(1920, true);
    }

    /**
     * Create from array
     *
     * @param array $config Configuration array.
     * @return self Configuration instance.
     */
    public static function from_array(array $config): self
    {
        return new self(
            $config['max_image_width'] ?? 1920,
            $config['resize_on_upload'] ?? true
        );
    }

    /**
     * Get maximum image width
     *
     * @return int
     */
    public function get_max_image_width(): int
    {
        return $this->max_image_width;
    }

    /**
     * Check if resize on upload is enabled
     *
     * @return bool
     */
    public function should_resize_on_upload(): bool
    {
        return $this->resize_on_upload;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function to_array(): array
    {
        return [
            'max_image_width'    => $this->max_image_width,
            'resize_on_upload'   => $this->resize_on_upload,
        ];
    }
}
