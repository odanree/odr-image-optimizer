<?php

declare(strict_types=1);

/**
 * Image File Processor
 *
 * Handles filesystem operations for image conversion using WP_Image_Editor.
 * Generates ConversionResult objects for processed images.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

use ImageOptimizer\Interfaces\ImageConverterInterface;
use ImageOptimizer\Interfaces\ImageEditorFactoryInterface;
use ImageOptimizer\Result\ConversionResult;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Image file processor
 *
 * Uses WP_Image_Editor to process images and generate ConversionResult objects.
 * Separates filesystem concerns from metadata management.
 */
class ImageFileProcessor
{
    /**
     * Image editor factory (for DI)
     *
     * @var ImageEditorFactoryInterface
     */
    private ImageEditorFactoryInterface $factory;

    /**
     * Constructor
     *
     * @param ImageEditorFactoryInterface $factory Factory for creating WP_Image_Editor instances.
     */
    public function __construct(ImageEditorFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Process an image file with a converter
     *
     * Reads the image, applies the converter, and returns ConversionResult.
     * Single-file operation (not recursive into subsizes).
     *
     * @param string                   $filePath  Full filesystem path to image.
     * @param ImageConverterInterface  $converter Strategy for converting the image.
     *
     * @return ConversionResult Result of conversion (success/failure).
     */
    public function process(string $filePath, ImageConverterInterface $converter): ConversionResult
    {
        // Validate input
        if (! \file_exists($filePath)) {
            return new ConversionResult(
                success: false,
                outputPath: '',
                mimeType: '',
                error: 'File not found: ' . $filePath,
                dimensions: [],
            );
        }

        // Get WP_Image_Editor instance
        $editor = $this->factory->get($filePath);

        // Check for editor creation errors
        if ($editor instanceof \WP_Error) {
            return new ConversionResult(
                success: false,
                outputPath: '',
                mimeType: '',
                error: $editor->get_error_message(),
                dimensions: [],
            );
        }

        if (! is_object($editor)) {
            return new ConversionResult(
                success: false,
                outputPath: '',
                mimeType: '',
                error: 'Unable to create image editor instance',
                dimensions: [],
            );
        }

        // Get image dimensions before conversion
        $size = $editor->get_size();
        if (! is_array($size) || ! isset($size['width'], $size['height'])) {
            return new ConversionResult(
                success: false,
                outputPath: '',
                mimeType: '',
                error: 'Unable to read image dimensions',
                dimensions: [],
            );
        }

        $originalWidth = (int) $size['width'];
        $originalHeight = (int) $size['height'];

        // Apply converter strategy
        $result = $converter->convert($editor, $filePath);

        // If conversion failed, return the error result
        if (! $result->isSuccess()) {
            return $result;
        }

        // Success: return result with dimensions
        return new ConversionResult(
            success: true,
            outputPath: $result->outputPath,
            mimeType: $result->mimeType,
            error: null,
            dimensions: [
                'width'  => $originalWidth,
                'height' => $originalHeight,
            ],
        );
    }

    /**
     * Process subsizes (thumbnails, medium, large, etc.)
     *
     * Converts all subsizes listed in attachment metadata.
     * Returns array of size_name => ConversionResult pairs.
     *
     * @param int                      $attachmentId The attachment ID (for metadata lookup).
     * @param ImageConverterInterface  $converter    Strategy for conversion.
     * @param string                   $uploadDir    Full path to uploads directory.
     *
     * @return array<string, ConversionResult> Results indexed by size name.
     */
    public function process_subsizes(
        int $attachmentId,
        ImageConverterInterface $converter,
        string $uploadDir,
    ): array {
        $results = [];

        // Get metadata to find subsizes
        $metadata = \wp_get_attachment_metadata($attachmentId);
        if (! is_array($metadata) || ! isset($metadata['sizes'])) {
            return $results;
        }

        $sizes = $metadata['sizes'];
        if (! is_array($sizes)) {
            return $results;
        }

        // Process each subsize
        foreach ($sizes as $sizeName => $sizeData) {
            if (! is_string($sizeName) || ! is_array($sizeData)) {
                continue;
            }

            // Get filename from size data
            $file = $sizeData['file'] ?? '';
            if (! is_string($file) || empty($file)) {
                continue;
            }

            // Build full path
            $filePath = $uploadDir . '/' . $file;

            // Process this size
            $result = $this->process($filePath, $converter);
            $results[ $sizeName ] = $result;
        }

        return $results;
    }
}
