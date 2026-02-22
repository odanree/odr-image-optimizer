<?php

declare(strict_types=1);

/**
 * Image Converter Interface
 *
 * Strategy pattern for image format conversion.
 * Implementations handle specific formats (WebP, AVIF, etc.)
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Interfaces;

use ImageOptimizer\Result\ConversionResult;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Image converter interface
 *
 * Defines contract for converting images between formats.
 * Strategy pattern allows pluggable converters (WebP, AVIF, etc.)
 * without modifying orchestrator code.
 */
interface ImageConverterInterface
{
    /**
     * Convert an image using WP_Image_Editor
     *
     * @param \WP_Image_Editor $editor   Image editor instance with loaded image.
     * @param string           $filePath Original file path (for reference).
     *
     * @return ConversionResult Result with output path or error details.
     */
    public function convert(\WP_Image_Editor $editor, string $filePath): ConversionResult;
}
