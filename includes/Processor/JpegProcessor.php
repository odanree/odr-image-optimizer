<?php

declare(strict_types=1);




if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * JPEG Image Processor
 *
 * @package ImageOptimizer\Processor
 */

namespace ImageOptimizer\Processor;

use ImageOptimizer\Exception\OptimizationFailedException;

class JpegProcessor implements ImageProcessorInterface
{
    public function process(string $filePath, int $quality): bool
    {
        if (!file_exists($filePath)) {
            throw new OptimizationFailedException("File not found: {$filePath}");
        }

        if (!extension_loaded('gd')) {
            throw new OptimizationFailedException('GD extension is not loaded');
        }

        try {
            $image = imagecreatefromjpeg($filePath);
            if ($image === false) {
                throw new OptimizationFailedException('Failed to load JPEG image');
            }

            // Apply progressive encoding for better compression and perceived performance
            imageinterlace($image, true);

            $result = imagejpeg($image, $filePath, $quality);
            imagedestroy($image);

            return $result !== false;
        } catch (\Throwable $e) {
            throw new OptimizationFailedException("JPEG optimization failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'jpg' || $extension === 'jpeg';
    }

    public function getMimeType(): string
    {
        return 'image/jpeg';
    }
}
