<?php

declare(strict_types=1);

/**
 * Image Resizer Service
 *
 * Handles responsive image resizing to prevent serving oversized images.
 * Follows Single Responsibility Principle: Only scales images to appropriate dimensions.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Resizes images to prevent "Oversized Images" Lighthouse warning
 *
 * Responsibility: Calculate appropriate dimensions and scale images.
 * Does NOT know about compression, WebP, or other concerns.
 */
class ImageResizer
{
    /**
     * Tool registry for access to external tools
     *
     * @var ToolRegistry
     */
    private $tool_registry;

    /**
     * Resizer configuration
     *
     * @var ResizingConfig
     */
    private $config;

    /**
     * Constructor - inject dependencies
     *
     * @param ToolRegistry|null      $tool_registry Tool registry.
     * @param ResizingConfig|null    $config Resizing configuration.
     */
    public function __construct(?ToolRegistry $tool_registry = null, ?ResizingConfig $config = null)
    {
        $this->tool_registry = $tool_registry ?? new ToolRegistry();
        $this->config = $config ?? ResizingConfig::defaults();
    }

    /**
     * Scale image to maximum allowed width
     *
     * This prevents serving 3200px images in 700px containers.
     * Called BEFORE optimization so optimizer works on appropriately-sized files.
     *
     * @param int    $attachment_id WordPress attachment ID.
     * @param string $file_path Path to image file.
     * @param int    $current_width Current width in pixels.
     * @param int    $current_height Current height in pixels.
     * @return array Result array with 'resized' flag and new dimensions.
     */
    public function scale_to_max_width(
        int $attachment_id,
        string $file_path,
        int $current_width,
        int $current_height
    ): array {
        $max_width = $this->config->get_max_image_width();

        // If image is smaller than max, no resize needed
        if ($current_width <= $max_width) {
            return [
                'resized'       => false,
                'original_width'   => $current_width,
                'original_height'  => $current_height,
                'new_width'     => $current_width,
                'new_height'    => $current_height,
            ];
        }

        // Calculate new height maintaining aspect ratio
        $aspect_ratio = $current_height / $current_width;
        $new_height = (int) ($max_width * $aspect_ratio);

        // Attempt resize
        $resized = $this->resize_file($file_path, $max_width, $new_height);

        if (! $resized) {
            return [
                'resized'       => false,
                'error'         => 'Failed to resize image',
                'original_width'   => $current_width,
                'original_height'  => $current_height,
            ];
        }

        // Update attachment metadata
        $this->update_attachment_metadata($attachment_id, $max_width, $new_height);

        return [
            'resized'       => true,
            'original_width'   => $current_width,
            'original_height'  => $current_height,
            'new_width'     => $max_width,
            'new_height'    => $new_height,
            'bytes_saved'   => 0,  // Calculated after actual resize
        ];
    }

    /**
     * Resize image file
     *
     * Attempts ImageMagick first, falls back to GD.
     *
     * @param string $file_path Path to image file.
     * @param int    $new_width Target width.
     * @param int    $new_height Target height.
     * @return bool Success flag.
     */
    private function resize_file(string $file_path, int $new_width, int $new_height): bool
    {
        if (! file_exists($file_path) || ! is_writable($file_path)) {
            return false;
        }

        try {
            // Try ImageMagick first (better quality)
            if (extension_loaded('imagick')) {
                return $this->resize_imagick($file_path, $new_width, $new_height);
            }

            // Fall back to GD
            return $this->resize_gd($file_path, $new_width, $new_height);
        } catch (\Exception $e) {
            error_log('ImageOptimizer Resizer Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Resize using ImageMagick
     *
     * @param string $file_path Path to image file.
     * @param int    $new_width Target width.
     * @param int    $new_height Target height.
     * @return bool Success flag.
     */
    private function resize_imagick(string $file_path, int $new_width, int $new_height): bool
    {
        try {
            $image = new \Imagick($file_path);
            $image->resizeImage($new_width, $new_height, \Imagick::FILTER_LANCZOS, 1);
            $image->writeImage();
            $image->destroy();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Resize using GD Library
     *
     * @param string $file_path Path to image file.
     * @param int    $new_width Target width.
     * @param int    $new_height Target height.
     * @return bool Success flag.
     */
    private function resize_gd(string $file_path, int $new_width, int $new_height): bool
    {
        $file_info = getimagesize($file_path);
        if (! $file_info) {
            return false;
        }

        $source_image = null;
        switch ($file_info[2]) {
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($file_path);
                break;
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($file_path);
                break;
            default:
                return false;
        }

        if (! $source_image) {
            return false;
        }

        $new_image = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled(
            $new_image,
            $source_image,
            0, 0, 0, 0,
            $new_width,
            $new_height,
            $file_info[0],
            $file_info[1]
        );

        $success = false;
        switch ($file_info[2]) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($new_image, $file_path, 85);
                break;
            case IMAGETYPE_PNG:
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $success = imagepng($new_image, $file_path, 9);
                break;
        }

        imagedestroy($source_image);
        imagedestroy($new_image);

        return $success;
    }

    /**
     * Update attachment metadata with new dimensions
     *
     * @param int $attachment_id WordPress attachment ID.
     * @param int $new_width New width.
     * @param int $new_height New height.
     * @return bool Success flag.
     */
    private function update_attachment_metadata(int $attachment_id, int $new_width, int $new_height): bool
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (! is_array($metadata)) {
            $metadata = [];
        }

        $metadata['width'] = $new_width;
        $metadata['height'] = $new_height;

        return (bool) wp_update_attachment_metadata($attachment_id, $metadata);
    }

    /**
     * Get the resizing configuration
     *
     * @return ResizingConfig
     */
    public function get_config(): ResizingConfig
    {
        return $this->config;
    }
}
