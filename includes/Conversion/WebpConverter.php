<?php

declare(strict_types=1);




if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * WebP Converter - Converts images to WebP format
 *
 * @package ImageOptimizer\Conversion
 */

namespace ImageOptimizer\Conversion;

use ImageOptimizer\Exception\OptimizationFailedException;

readonly class WebpConverter
{
    public function __construct(
        private int $quality = 60,
    ) {}

    /**
     * Check if WebP conversion is possible
     *
     * @return bool
     */
    public function isSupported(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    /**
     * Convert an image to WebP format
     *
     * @param string $sourceFilePath Path to source image
     * @param int|null $quality WebP quality (0-100), null to use default
     * @return string Path to created WebP file
     *
     * @throws OptimizationFailedException
     */
    public function convert(string $sourceFilePath, ?int $quality = null): string
    {
        $quality ??= $this->quality;

        if (!file_exists($sourceFilePath)) {
            throw new OptimizationFailedException("Source file not found: {$sourceFilePath}");
        }

        if (!$this->isSupported()) {
            throw new OptimizationFailedException('WebP support not available (GD extension required)');
        }

        // Only convert JPEG and PNG to WebP
        if (!$this->canConvertFile($sourceFilePath)) {
            throw new OptimizationFailedException("Cannot convert file to WebP: {$sourceFilePath}");
        }

        try {
            $webpPath = $this->getWebpPath($sourceFilePath);

            // Skip if WebP already exists
            if (file_exists($webpPath)) {
                return $webpPath;
            }

            $image = $this->loadImage($sourceFilePath);
            if ($image === false) {
                throw new OptimizationFailedException('Failed to load image for WebP conversion');
            }

            if (!imagewebp($image, $webpPath, $quality)) {
                imagedestroy($image);
                throw new OptimizationFailedException('Failed to create WebP file');
            }

            imagedestroy($image);
            return $webpPath;
        } catch (OptimizationFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new OptimizationFailedException("WebP conversion failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Check if this file type can be converted to WebP
     *
     * @param string $filePath
     * @return bool
     */
    public function canConvertFile(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png'], true);
    }

    /**
     * Get the WebP path for a source file
     *
     * @param string $sourceFilePath
     * @return string
     */
    private function getWebpPath(string $sourceFilePath): string
    {
        return $sourceFilePath . '.webp';
    }

    /**
     * Load an image resource from file
     *
     * @param string $filePath
     * @return \GdImage|false
     */
    private function loadImage(string $filePath): \GdImage|false
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => imagecreatefrompng($filePath),
            'jpg', 'jpeg' => imagecreatefromjpeg($filePath),
            default => false,
        };
    }

    /**
     * Delete the WebP version of a file
     *
     * @param string $sourceFilePath
     * @return bool
     */
    public function deleteWebpVersion(string $sourceFilePath): bool
    {
        $webpPath = $this->getWebpPath($sourceFilePath);

        if (!file_exists($webpPath)) {
            return true; // Already deleted or doesn't exist
        }

        return @unlink($webpPath);
    }
}
