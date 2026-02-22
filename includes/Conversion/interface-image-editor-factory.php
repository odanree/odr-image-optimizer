<?php

declare(strict_types=1);

/**
 * Image Editor Factory Interface
 *
 * Dependency Injection pattern for WP_Image_Editor.
 * Allows mocking in tests without filesystem access.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Conversion;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Image editor factory contract
 *
 * Creates WP_Image_Editor instances for a given file path.
 * By injecting the factory instead of hardcoding `wp_get_image_editor()`,
 * tests can provide a mock factory that returns mock editors without touching disk.
 *
 * Example Usage in Tests:
 * ```php
 * $mockFactory = $this->createMock(ImageEditorFactoryInterface::class);
 * $mockEditor = $this->createMock(WP_Image_Editor::class);
 * $mockFactory->method('get')->willReturn($mockEditor);
 *
 * $processor = new ImageFileProcessor($mockFactory);
 * // Now processor uses mock editor, no filesystem involved
 * ```
 */
interface ImageEditorFactoryInterface
{
    /**
     * Get an image editor for the given file path
     *
     * @param string $path Full filesystem path to image file.
     *
     * @return mixed Editor instance, or false if creation failed.
     *               In practice: WP_Image_Editor|false|WP_Error
     *               Use is_wp_error() to check for errors in production.
     */
    public function get(string $path);

    /**
     * Check if the editor implementation supports this mime type
     *
     * @param string $mimeType MIME type (e.g., 'image/jpeg').
     *
     * @return bool
     */
    public function supports(string $mimeType): bool;
}
