<?php

declare(strict_types=1);

/**
 * WebP Image Processor
 *
 * @package ImageOptimizer\Processor
 */

namespace ImageOptimizer\Processor;

use ImageOptimizer\Exception\OptimizationFailedException;

class WebpProcessor implements ImageProcessorInterface
{
    public function process(string $filePath, int $quality): bool
    {
        if (!file_exists($filePath)) {
            throw new OptimizationFailedException("File not found: {$filePath}");
        }

        if (!extension_loaded('gd')) {
            throw new OptimizationFailedException('GD extension is not loaded');
        }

        if (!function_exists('imagewebp')) {
            throw new OptimizationFailedException('WebP support is not available in GD');
        }

        try {
            $image = imagecreatefromwebp($filePath);
            if ($image === false) {
                throw new OptimizationFailedException('Failed to load WebP image');
            }

            $result = imagewebp($image, $filePath, $quality);
            imagedestroy($image);

            return $result !== false;
        } catch (\Throwable $e) {
            throw new OptimizationFailedException("WebP optimization failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'webp';
    }

    public function getMimeType(): string
    {
        return 'image/webp';
    }
}
