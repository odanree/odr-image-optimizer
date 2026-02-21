<?php

declare(strict_types=1);

/**
 * Resizing Processor
 *
 * Integrates image resizing into the optimization pipeline.
 * Follows Open/Closed Principle: Extends core behavior through hooks.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Processes image resizing in the optimization pipeline
 */
class ResizingProcessor
{
    /**
     * Image resizer service
     *
     * @var ImageResizer
     */
    private $resizer;

    /**
     * Constructor
     *
     * @param ImageResizer|null $resizer Image resizer service.
     */
    public function __construct(?ImageResizer $resizer = null)
    {
        $this->resizer = $resizer ?? new ImageResizer();
    }

    /**
     * Register hooks to integrate resizing into optimization pipeline
     *
     * Hooks at the appropriate points:
     * 1. wp_generate_attachment_metadata - Process resizing on upload
     * 2. image_optimizer_before_optimize - Resize before compression
     */
    public function register_hooks(): void
    {
        // Resize on upload (before optimization)
        add_filter('wp_generate_attachment_metadata', [ $this, 'resize_on_metadata_generation' ], 9, 2);

        // Hook into optimization pipeline to pass resize information
        add_filter('image_optimizer_before_optimize', [ $this, 'maybe_resize_before_optimize' ], 10, 1);
    }

    /**
     * Resize image when metadata is generated (WordPress upload hook)
     *
     * This runs BEFORE the optimizer, so we resize to appropriate size first,
     * then let the optimizer compress the appropriately-sized image.
     *
     * @param array $metadata Attachment metadata.
     * @param int   $attachment_id Attachment ID.
     * @return array Modified metadata.
     */
    public function resize_on_metadata_generation(array $metadata, int $attachment_id): array
    {
        if (! $this->resizer->get_config()->should_resize_on_upload()) {
            return $metadata;
        }

        // Get file path
        $file = get_attached_file($attachment_id);
        if (! $file || ! file_exists($file)) {
            return $metadata;
        }

        // Get current dimensions
        $width = $metadata['width'] ?? 0;
        $height = $metadata['height'] ?? 0;

        if ($width <= 0 || $height <= 0) {
            return $metadata;
        }

        // Attempt resize
        $result = $this->resizer->scale_to_max_width($attachment_id, $file, $width, $height);

        if ($result['resized']) {
            // Update metadata with new dimensions
            $metadata['width'] = $result['new_width'];
            $metadata['height'] = $result['new_height'];
        }

        return $metadata;
    }

    /**
     * Hook into optimization pipeline to resize if needed
     *
     * Fired by do_action('image_optimizer_before_optimize', $context)
     * Allows resizing to happen before compression.
     *
     * @param ImageContext $context Image context with attachment data.
     */
    public function maybe_resize_before_optimize(ImageContext $context): void
    {
        if (! $this->resizer->get_config()->should_resize_on_upload()) {
            return;
        }

        $attachment_id = $context->get('attachment_id');
        $file = $context->get('file_path');
        $width = $context->get('width');
        $height = $context->get('height');

        if (! $file || ! $width || ! $height) {
            return;
        }

        // Attempt resize
        $result = $this->resizer->scale_to_max_width($attachment_id, $file, $width, $height);

        if ($result['resized']) {
            // Update context with new dimensions so optimizer knows the size changed
            $context->set('width', $result['new_width']);
            $context->set('height', $result['new_height']);
            $context->set('resized', true);
        }
    }

    /**
     * Get the resizer service
     *
     * @return ImageResizer
     */
    public function get_resizer(): ImageResizer
    {
        return $this->resizer;
    }
}
