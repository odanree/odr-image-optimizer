<?php

declare(strict_types=1);

/**
 * Image Context Data Object
 *
 * Passes image metadata through hooks to avoid re-reading files from disk.
 * Allows hooked functions to access metadata without additional I/O overhead.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Image context for hooks
 *
 * Usage:
 *   $context = new ImageContext($file_path, $attachment_id, [
 *       'width' => 3200,
 *       'height' => 2133,
 *       'original_size' => 1310720,
 *       'mime_type' => 'image/jpeg',
 *   ]);
 *
 *   apply_filters('image_optimizer_before_optimize', $context);
 */
class ImageContext
{
    /**
     * File path to the image
     *
     * @var string
     */
    public $file_path;

    /**
     * WordPress attachment ID
     *
     * @var int
     */
    public $attachment_id;

    /**
     * Image width in pixels
     *
     * @var int
     */
    public $width;

    /**
     * Image height in pixels
     *
     * @var int
     */
    public $height;

    /**
     * Original file size in bytes
     *
     * @var int
     */
    public $original_size;

    /**
     * MIME type (e.g., image/jpeg, image/png)
     *
     * @var string
     */
    public $mime_type;

    /**
     * Compression level (high, medium, low)
     *
     * @var string
     */
    public $compression_level;

    /**
     * Additional metadata
     *
     * @var array
     */
    public $metadata;

    /**
     * Constructor
     *
     * @param string $file_path The file path.
     * @param int    $attachment_id The attachment ID.
     * @param array  $metadata Additional metadata.
     */
    public function __construct(string $file_path, int $attachment_id, array $metadata = [])
    {
        $this->file_path = $file_path;
        $this->attachment_id = $attachment_id;
        $this->metadata = $metadata;

        // Extract and set common properties
        $this->width = $metadata['width'] ?? 0;
        $this->height = $metadata['height'] ?? 0;
        $this->original_size = $metadata['original_size'] ?? filesize($file_path);
        $this->mime_type = $metadata['mime_type'] ?? mime_content_type($file_path);
        $this->compression_level = $metadata['compression_level'] ?? 'medium';
    }

    /**
     * Get a metadata value
     *
     * @param string $key The metadata key.
     * @param mixed  $default Default value if key not found.
     * @return mixed The metadata value.
     */
    public function get(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set a metadata value
     *
     * @param string $key The metadata key.
     * @param mixed  $value The value.
     */
    public function set(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    /**
     * Get file size in MB (useful for logging/filtering)
     *
     * @return float Size in MB.
     */
    public function get_size_mb(): float
    {
        return $this->original_size / 1024 / 1024;
    }

    /**
     * Get dimensions as string (useful for logging)
     *
     * @return string Dimensions (e.g., "3200x2133").
     */
    public function get_dimensions(): string
    {
        return "{$this->width}x{$this->height}";
    }

    /**
     * Check if image is larger than a given width
     *
     * Useful for hooked functions deciding whether to process:
     * if ($context->is_larger_than(2000)) { process WebP }
     *
     * @param int $width Width threshold.
     * @return bool True if image width is larger.
     */
    public function is_larger_than(int $width): bool
    {
        return $this->width > $width;
    }
}
