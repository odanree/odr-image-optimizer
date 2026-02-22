<?php

declare(strict_types=1);

/**
 * WebP Converter
 *
 * Converts images to WebP format using WP_Image_Editor.
 * Implements ImageConverterInterface for strategy pattern.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

use ImageOptimizer\Interfaces\ImageConverterInterface;
use ImageOptimizer\Result\ConversionResult;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * WebP converter strategy
 *
 * Converts JPEG/PNG images to WebP format with quality settings.
 * Quality adjusts based on image dimensions (mobile-first optimization).
 */
class WebPConverter implements ImageConverterInterface
{
    /**
     * Quality for mobile images (< 800px)
     *
     * @var int
     */
    private int $mobileQuality = 70;

    /**
     * Quality for tablet images (800px - 1200px)
     *
     * @var int
     */
    private int $tabletQuality = 75;

    /**
     * Quality for desktop images (> 1200px)
     *
     * @var int
     */
    private int $desktopQuality = 82;

    /**
     * Constructor
     *
     * @param int $mobileQuality  Optional quality for mobile (default 70).
     * @param int $tabletQuality  Optional quality for tablet (default 75).
     * @param int $desktopQuality Optional quality for desktop (default 82).
     */
    public function __construct(
        int $mobileQuality = 70,
        int $tabletQuality = 75,
        int $desktopQuality = 82,
    ) {
        $this->mobileQuality = $mobileQuality;
        $this->tabletQuality = $tabletQuality;
        $this->desktopQuality = $desktopQuality;
    }

    /**
     * Convert image to WebP format
     *
     * Uses WP_Image_Editor to handle conversion with quality based on image dimensions.
     *
     * @param \WP_Image_Editor $editor   The image editor instance.
     * @param string           $filePath Full filesystem path to original image.
     *
     * @return ConversionResult Result with WebP output path or error.
     */
    public function convert(\WP_Image_Editor $editor, string $filePath): ConversionResult
    {
        // Determine quality based on image width
        $quality = $this->get_quality_for_image($editor);

        // Set WebP format and quality
        $editor->set_quality($quality);
        $editor->set_mime_type('image/webp');

        // Generate output filename (replace extension with .webp)
        $outputPath = $this->get_webp_path($filePath);

        // Save as WebP
        $result = $editor->save($outputPath);

        // Check for save errors
        if ($result instanceof \WP_Error) {
            return new ConversionResult(
                success: false,
                outputPath: '',
                mimeType: '',
                error: $result->get_error_message(),
                dimensions: [],
            );
        }

        // Verify file was created
        if (! \file_exists($outputPath)) {
            return new ConversionResult(
                success: false,
                outputPath: '',
                mimeType: '',
                error: 'WebP file was not created',
                dimensions: [],
            );
        }

        // Success
        return new ConversionResult(
            success: true,
            outputPath: $outputPath,
            mimeType: 'image/webp',
            error: null,
            dimensions: [],
        );
    }

    /**
     * Get quality level based on image dimensions
     *
     * @param \WP_Image_Editor $editor The image editor instance.
     *
     * @return int Quality percentage (0-100).
     */
    private function get_quality_for_image(\WP_Image_Editor $editor): int
    {
        $size = $editor->get_size();
        if (! is_array($size) || ! isset($size['width'])) {
            return $this->desktopQuality;
        }

        $width = (int) $size['width'];

        // Mobile: < 800px
        if ($width < 800) {
            return $this->mobileQuality;
        }

        // Tablet: 800px - 1200px
        if ($width <= 1200) {
            return $this->tabletQuality;
        }

        // Desktop: > 1200px
        return $this->desktopQuality;
    }

    /**
     * Convert filename to WebP path
     *
     * @param string $filePath Original file path.
     *
     * @return string WebP file path.
     */
    private function get_webp_path(string $filePath): string
    {
        return \str_replace(
            [ '.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG' ],
            '.webp',
            $filePath,
        );
    }
}
