<?php

declare(strict_types=1);

/**
 * Image Processor Strategy Interface
 *
 * @package ImageOptimizer\Processor
 */

namespace ImageOptimizer\Processor;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}
interface ImageProcessorInterface
{
    /**
     * Process an image file
     *
     * @param string $filePath The file path to process
     * @param int $quality Quality/compression level
     * @return bool Success status
     *
     * @throws \ImageOptimizer\Exception\OptimizationFailedException
     */
    public function process(string $filePath, int $quality): bool;

    /**
     * Check if this processor can handle the file type
     *
     * @param string $filePath The file path
     * @return bool
     */
    public function supports(string $filePath): bool;

    /**
     * Get the MIME type this processor handles
     *
     * @return string
     */
    public function getMimeType(): string;
}
