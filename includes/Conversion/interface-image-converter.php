<?php

declare(strict_types=1);

/**
 * Image Converter Interface
 *
 * Strategy pattern for pluggable image conversion (WebP, AVIF, etc.).
 * Allows future formats without modifying orchestrator logic.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Conversion;

use ImageOptimizer\Result\ConversionResult;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Image converter contract
 *
 * Implementations convert a source image to a specific format.
 * Each converter is independent and testable.
 *
 * Strategy Pattern Example:
 * - WebPConverter: source.jpg → source.webp
 * - AVIFConverter: source.jpg → source.avif (future)
 * - HeicConverter: source.jpg → source.heic (future)
 */
interface ImageConverterInterface
{
    /**
     * Convert source image to target format
     *
     * @param string $sourcePath Full filesystem path to source image.
     * @param array<string, mixed>  $options    Conversion options (quality, compression, dimensions, etc.).
     *
     * @return ConversionResult Immutable result with success/failure and output details.
     */
    public function convert(string $sourcePath, array $options): ConversionResult;

    /**
     * Get the MIME type this converter produces
     *
     * @return string e.g., 'image/webp', 'image/avif'
     */
    public function getMimeType(): string;

    /**
     * Get the file extension this converter produces
     *
     * @return string e.g., 'webp', 'avif'
     */
    public function getExtension(): string;
}
