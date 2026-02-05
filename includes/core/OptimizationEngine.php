<?php
declare(strict_types=1);

/**
 * Main Optimization Engine - SRP compliant, fully decoupled
 *
 * @package ImageOptimizer\Core
 */

namespace ImageOptimizer\Core;

use ImageOptimizer\Backup\BackupManager;
use ImageOptimizer\Configuration\OptimizationConfig;
use ImageOptimizer\Exception\OptimizationFailedException;
use ImageOptimizer\Processor\ImageProcessorInterface;
use ImageOptimizer\Repository\DatabaseRepository;

readonly class OptimizationEngine
{
    /**
     * @param BackupManager $backupManager
     * @param DatabaseRepository $repository
     * @param ImageProcessorInterface[] $processors
     */
    public function __construct(
        private BackupManager $backupManager,
        private DatabaseRepository $repository,
        private array $processors,
    ) {}

    /**
     * Optimize an image file
     *
     * @param string $filePath Path to the image file
     * @param string $identifier Unique identifier (e.g., attachment ID)
     * @param OptimizationConfig $config Configuration for optimization
     * @return array Result including sizes and compression ratio
     *
     * @throws OptimizationFailedException
     */
    public function optimize(
        string $filePath,
        string $identifier,
        OptimizationConfig $config,
    ): array {
        if (!file_exists($filePath)) {
            throw new OptimizationFailedException("File not found: {$filePath}");
        }

        try {
            // Get original file size
            $originalSize = filesize($filePath);
            if ($originalSize === false) {
                throw new OptimizationFailedException("Cannot determine file size: {$filePath}");
            }

            // Create backup before optimization
            $backupPath = $this->backupManager->createBackup($filePath, $identifier);

            // Find appropriate processor
            $processor = $this->findProcessor($filePath);
            if ($processor === null) {
                throw new OptimizationFailedException("No processor available for file type: {$filePath}");
            }

            // Determine quality/compression parameters
            $quality = $this->getQualityForProcessor($processor, $config);

            // Perform optimization
            $processor->process($filePath, $quality);

            // Get optimized file size
            $optimizedSize = filesize($filePath);
            if ($optimizedSize === false) {
                throw new OptimizationFailedException("Cannot determine optimized file size");
            }

            $savings = $originalSize - $optimizedSize;
            $compressionRatio = $savings > 0 ? ($savings / $originalSize) * 100 : 0;

            // Try to create WebP version if enabled
            $webpAvailable = false;
            if ($config->enableWebp) {
                $webpAvailable = $this->tryCreateWebpVersion($filePath, $config);
            }

            return [
                'success' => true,
                'originalSize' => $originalSize,
                'optimizedSize' => $optimizedSize,
                'savings' => $savings,
                'compressionRatio' => $compressionRatio,
                'webpAvailable' => $webpAvailable,
                'backupPath' => $backupPath,
            ];
        } catch (OptimizationFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new OptimizationFailedException("Optimization failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Revert an optimized image to its backup
     *
     * @param string $filePath Path to the image file
     * @param string $identifier Unique identifier
     * @return array Result including restored size
     *
     * @throws OptimizationFailedException
     */
    public function revert(string $filePath, string $identifier): array
    {
        try {
            if (!file_exists($filePath)) {
                throw new OptimizationFailedException("File not found: {$filePath}");
            }

            $optimizedSize = filesize($filePath);

            // Restore from backup
            $this->backupManager->restore($filePath, $identifier);

            $restoredSize = filesize($filePath);
            if ($restoredSize === false) {
                throw new OptimizationFailedException("Cannot determine restored file size");
            }

            // Delete WebP version if it exists
            $webpPath = $filePath . '.webp';
            if (file_exists($webpPath)) {
                @unlink($webpPath);
            }

            $freedSpace = $optimizedSize - $restoredSize;

            return [
                'success' => true,
                'restoredSize' => $restoredSize,
                'freedSpace' => $freedSpace,
            ];
        } catch (OptimizationFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new OptimizationFailedException("Revert failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Find the appropriate processor for a file
     *
     * @param string $filePath
     * @return ImageProcessorInterface|null
     */
    private function findProcessor(string $filePath): ?ImageProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($filePath)) {
                return $processor;
            }
        }

        return null;
    }

    /**
     * Get quality/compression parameters for a processor
     *
     * @param ImageProcessorInterface $processor
     * @param OptimizationConfig $config
     * @return int
     */
    private function getQualityForProcessor(
        ImageProcessorInterface $processor,
        OptimizationConfig $config,
    ): int {
        return match ($processor->getMimeType()) {
            'image/jpeg' => $this->getJpegQuality($config->compressionLevel, $config->jpegQuality),
            'image/png' => $this->getPngCompressionLevel($config->compressionLevel, $config->pngCompressionLevel),
            'image/webp' => $config->webpQuality,
            default => 80,
        };
    }

    /**
     * Get JPEG quality level based on compression setting
     *
     * @param string $compressionLevel
     * @param int $defaultQuality
     * @return int
     */
    private function getJpegQuality(string $compressionLevel, int $defaultQuality): int
    {
        return match ($compressionLevel) {
            'low' => 80,
            'medium' => 70,
            'high' => 60,
            default => $defaultQuality,
        };
    }

    /**
     * Get PNG compression level based on compression setting
     *
     * @param string $compressionLevel
     * @param int $defaultLevel
     * @return int
     */
    private function getPngCompressionLevel(string $compressionLevel, int $defaultLevel): int
    {
        $level = match ($compressionLevel) {
            'low' => 7,
            'medium' => 8,
            'high' => 9,
            default => $defaultLevel,
        };

        // Ensure within valid range
        return max(0, min(9, $level));
    }

    /**
     * Attempt to create a WebP version of the image
     *
     * @param string $filePath
     * @param OptimizationConfig $config
     * @return bool
     */
    private function tryCreateWebpVersion(string $filePath, OptimizationConfig $config): bool
    {
        try {
            if (!extension_loaded('gd') || !function_exists('imagewebp')) {
                return false;
            }

            // Only create WebP for JPEG and PNG
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                return false;
            }

            $webpPath = $filePath . '.webp';
            if (file_exists($webpPath)) {
                return true; // Already exists
            }

            $image = match ($extension) {
                'png' => imagecreatefrompng($filePath),
                default => imagecreatefromjpeg($filePath),
            };

            if ($image === false) {
                return false;
            }

            $result = imagewebp($image, $webpPath, $config->webpQuality);
            imagedestroy($image);

            return $result !== false;
        } catch (\Throwable) {
            return false;
        }
    }
}
