<?php

declare(strict_types=1);

/**
 * Optimizer Configuration Value Object
 *
 * Encapsulates all optimizer settings.
 * Prevents Optimizer from calling get_option() internally (DIP compliance).
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Immutable configuration for optimizer
 */
class OptimizerConfig
{
    /**
     * Auto-optimization on upload
     *
     * @var bool
     */
    private $auto_optimize;

    /**
     * JPEG quality level
     *
     * @var int
     */
    private $jpeg_quality;

    /**
     * PNG compression level
     *
     * @var int
     */
    private $png_compression;

    /**
     * Create WebP versions
     *
     * @var bool
     */
    private $create_webp;

    /**
     * WebP quality level
     *
     * @var int
     */
    private $webp_quality;

    /**
     * Strip metadata
     *
     * @var bool
     */
    private $strip_metadata;

    /**
     * Use progressive encoding
     *
     * @var bool
     */
    private $progressive_encoding;

    /**
     * Constructor (private - use factory methods)
     *
     * @param bool $auto_optimize Enable auto-optimization.
     * @param int  $jpeg_quality JPEG quality (1-100).
     * @param int  $png_compression PNG compression (0-9).
     * @param bool $create_webp Create WebP versions.
     * @param int  $webp_quality WebP quality (1-100).
     * @param bool $strip_metadata Strip image metadata.
     * @param bool $progressive_encoding Use progressive encoding.
     */
    private function __construct(
        bool $auto_optimize = false,
        int $jpeg_quality = 75,
        int $png_compression = 9,
        bool $create_webp = true,
        int $webp_quality = 75,
        bool $strip_metadata = true,
        bool $progressive_encoding = true
    ) {
        $this->auto_optimize = $auto_optimize;
        $this->jpeg_quality = max(1, min(100, $jpeg_quality));
        $this->png_compression = max(0, min(9, $png_compression));
        $this->create_webp = $create_webp;
        $this->webp_quality = max(1, min(100, $webp_quality));
        $this->strip_metadata = $strip_metadata;
        $this->progressive_encoding = $progressive_encoding;
    }

    /**
     * Create from WordPress options
     *
     * Fetches settings from WordPress and creates config.
     * This is where get_option() SHOULD be called - in the factory, not in the service.
     *
     * @return self Configuration instance.
     */
    public static function from_wordpress_options(): self
    {
        $settings = get_option('image_optimizer_settings', []);

        return new self(
            $settings['auto_optimize'] ?? false,
            $settings['jpeg_quality'] ?? 75,
            $settings['png_compression'] ?? 9,
            $settings['create_webp'] ?? true,
            $settings['webp_quality'] ?? 75,
            $settings['strip_metadata'] ?? true,
            $settings['progressive_encoding'] ?? true
        );
    }

    /**
     * Create with default values
     *
     * @return self Configuration instance.
     */
    public static function defaults(): self
    {
        return new self();
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
            $config['auto_optimize'] ?? false,
            $config['jpeg_quality'] ?? 75,
            $config['png_compression'] ?? 9,
            $config['create_webp'] ?? true,
            $config['webp_quality'] ?? 75,
            $config['strip_metadata'] ?? true,
            $config['progressive_encoding'] ?? true
        );
    }

    /**
     * Get auto-optimize setting
     *
     * @return bool
     */
    public function should_auto_optimize(): bool
    {
        return $this->auto_optimize;
    }

    /**
     * Get JPEG quality
     *
     * @return int
     */
    public function get_jpeg_quality(): int
    {
        return $this->jpeg_quality;
    }

    /**
     * Get PNG compression level
     *
     * @return int
     */
    public function get_png_compression(): int
    {
        return $this->png_compression;
    }

    /**
     * Check if WebP creation enabled
     *
     * @return bool
     */
    public function should_create_webp(): bool
    {
        return $this->create_webp;
    }

    /**
     * Get WebP quality
     *
     * @return int
     */
    public function get_webp_quality(): int
    {
        return $this->webp_quality;
    }

    /**
     * Check if metadata stripping enabled
     *
     * @return bool
     */
    public function should_strip_metadata(): bool
    {
        return $this->strip_metadata;
    }

    /**
     * Check if progressive encoding enabled
     *
     * @return bool
     */
    public function should_use_progressive_encoding(): bool
    {
        return $this->progressive_encoding;
    }

    /**
     * Convert to array (for serialization, logging, etc)
     *
     * @return array
     */
    public function to_array(): array
    {
        return [
            'auto_optimize'        => $this->auto_optimize,
            'jpeg_quality'         => $this->jpeg_quality,
            'png_compression'      => $this->png_compression,
            'create_webp'          => $this->create_webp,
            'webp_quality'         => $this->webp_quality,
            'strip_metadata'       => $this->strip_metadata,
            'progressive_encoding' => $this->progressive_encoding,
        ];
    }
}
