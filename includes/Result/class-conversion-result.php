<?php

declare(strict_types=1);

/**
 * Conversion Result DTO
 *
 * Immutable result object for image conversion operations.
 * Uses PHP 8.2 readonly properties to prevent mutation during transport.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Result;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Conversion result data transfer object
 *
 * Holds the complete state of an image conversion operation.
 * All properties are readonly, ensuring data integrity across service boundaries.
 *
 * @phpstan-type DimensionsArray array{width: int, height: int}
 */
readonly class ConversionResult
{
    /**
     * Constructor
     *
     * @param bool   $success      Whether conversion succeeded.
     * @param string $outputPath   Full filesystem path to converted file (empty if failed).
     * @param string $mimeType     MIME type of output file (e.g., 'image/webp').
     * @param ?string $error       Error message if failed (null if successful).
     * @param array<string, int>  $dimensions   Width and height of converted image.
     */
    public function __construct(
        public bool $success,
        public string $outputPath = '',
        public string $mimeType = '',
        public ?string $error = null,
        public array $dimensions = [],
    ) {}

    /**
     * Convenience method: Was conversion successful?
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Convenience method: Was conversion a failure?
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return ! $this->success;
    }

    /**
     * Get width from dimensions
     *
     * @return int
     */
    public function getWidth(): int
    {
        return $this->dimensions['width'] ?? 0;
    }

    /**
     * Get height from dimensions
     *
     * @return int
     */
    public function getHeight(): int
    {
        return $this->dimensions['height'] ?? 0;
    }
}
