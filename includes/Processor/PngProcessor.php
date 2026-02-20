<?php

declare(strict_types=1);




if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * PNG Image Processor
 *
 * @package ImageOptimizer\Processor
 */

namespace ImageOptimizer\Processor;

use ImageOptimizer\Exception\OptimizationFailedException;

class PngProcessor implements ImageProcessorInterface
{
    public function process(string $filePath, int $compressionLevel): bool
    {
        if (!file_exists($filePath)) {
            throw new OptimizationFailedException("File not found: {$filePath}");
        }

        if (!extension_loaded('gd')) {
            throw new OptimizationFailedException('GD extension is not loaded');
        }

        // Ensure compression level is within valid range (0-9)
        $level = max(0, min(9, $compressionLevel));

        try {
            $image = imagecreatefrompng($filePath);
            if ($image === false) {
                throw new OptimizationFailedException('Failed to load PNG image');
            }

            // Apply interlacing for progressive display
            imageinterlace($image, true);

            $result = imagepng($image, $filePath, $level);
            imagedestroy($image);

            return $result !== false;
        } catch (\Throwable $e) {
            throw new OptimizationFailedException("PNG optimization failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function supports(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return $extension === 'png';
    }

    public function getMimeType(): string
    {
        return 'image/png';
    }
}
