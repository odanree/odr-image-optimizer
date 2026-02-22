<?php

declare(strict_types=1);

/**
 * Image Editor Factory Interface
 *
 * Dependency injection for WP_Image_Editor creation.
 * Allows testing without real filesystem operations.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Interfaces;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Image editor factory interface
 *
 * Defines contract for creating WP_Image_Editor instances.
 * Enables dependency injection and testing with mock editors.
 */
interface ImageEditorFactoryInterface
{
    /**
     * Get an image editor instance
     *
     * Creates and returns a WP_Image_Editor for the given file,
     * or returns a WP_Error if creation fails.
     *
     * @param string $filePath Full filesystem path to image file.
     *
     * @return \WP_Image_Editor|\WP_Error Loaded image editor or error.
     */
    public function get(string $filePath);
}
