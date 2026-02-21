<?php

declare(strict_types=1);

/**
 * Image File Value Object
 *
 * Represents an image file with metadata, avoiding raw string paths.
 * Provides type safety and self-documenting code.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Image file value object
 *
 * Encapsulates image file information and provides type-safe access.
 *
 * @property-read string $path Full file path
 * @property-read int $attachment_id WordPress attachment ID
 * @property-read string $mime_type MIME type
 */
class ImageFile
{
    /**
     * Full path to the image file
     *
     * @var string
     */
    private $path;

    /**
     * WordPress attachment ID
     *
     * @var int
     */
    private $attachment_id;

    /**
     * MIME type (e.g., image/jpeg)
     *
     * @var string
     */
    private $mime_type;

    /**
     * Constructor
     *
     * @param string $path The file path.
     * @param int    $attachment_id The attachment ID.
     * @param string $mime_type The MIME type.
     *
     * @throws \InvalidArgumentException If file doesn't exist.
     */
    public function __construct(string $path, int $attachment_id, string $mime_type)
    {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("File does not exist: $path");
        }

        $this->path = $path;
        $this->attachment_id = $attachment_id;
        $this->mime_type = $mime_type;
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function get_path(): string
    {
        return $this->path;
    }

    /**
     * Get attachment ID
     *
     * @return int
     */
    public function get_attachment_id(): int
    {
        return $this->attachment_id;
    }

    /**
     * Get MIME type
     *
     * @return string
     */
    public function get_mime_type(): string
    {
        return $this->mime_type;
    }

    /**
     * Get file size in bytes
     *
     * @return int
     */
    public function get_size(): int
    {
        return filesize($this->path);
    }

    /**
     * Get file size in MB
     *
     * @return float
     */
    public function get_size_mb(): float
    {
        return $this->get_size() / 1024 / 1024;
    }

    /**
     * Get directory path
     *
     * @return string
     */
    public function get_directory(): string
    {
        return dirname($this->path);
    }

    /**
     * Get filename without extension
     *
     * @return string
     */
    public function get_basename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function get_extension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Check if file is JPEG
     *
     * @return bool
     */
    public function is_jpeg(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/jpg'], true);
    }

    /**
     * Check if file is PNG
     *
     * @return bool
     */
    public function is_png(): bool
    {
        return $this->mime_type === 'image/png';
    }

    /**
     * Check if file is WebP
     *
     * @return bool
     */
    public function is_webp(): bool
    {
        return $this->mime_type === 'image/webp';
    }

    /**
     * Get image dimensions
     *
     * @return array Array with [width, height] or empty if can't determine.
     */
    public function get_dimensions(): array
    {
        $info = @getimagesize($this->path);
        if ($info) {
            return [$info[0], $info[1]];
        }
        return [0, 0];
    }

    /**
     * Check if file is readable
     *
     * @return bool
     */
    public function is_readable(): bool
    {
        return is_readable($this->path);
    }

    /**
     * Check if file is writable
     *
     * @return bool
     */
    public function is_writable(): bool
    {
        return is_writable($this->path);
    }

    /**
     * Magic getter for property access
     *
     * @param string $name Property name.
     * @return mixed Property value.
     *
     * @throws \LogicException If property doesn't exist.
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'path':
                return $this->path;
            case 'attachment_id':
                return $this->attachment_id;
            case 'mime_type':
                return $this->mime_type;
            default:
                throw new \LogicException("Undefined property: $name");
        }
    }

    /**
     * Prevent property modification
     *
     * @param string $name Property name.
     * @param mixed  $value Property value.
     *
     * @throws \LogicException Always.
     */
    public function __set(string $name, $value)
    {
        throw new \LogicException('ImageFile is immutable');
    }
}
