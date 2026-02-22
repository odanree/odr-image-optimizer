<?php

declare(strict_types=1);

/**
 * Orchestrator Factory
 *
 * Creates and configures the BulkOptimizationOrchestrator with dependencies.
 * Centralizes orchestrator instantiation for reuse across codebase.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Orchestrator factory
 *
 * Creates fully-initialized orchestrator with all dependencies.
 * Ensures consistent configuration and lifecycle management.
 */
class OrchestratorFactory
{
    /**
     * Create an orchestrator instance
     *
     * Builds all dependencies and returns configured orchestrator.
     *
     * @return BulkOptimizationOrchestrator Fully initialized orchestrator.
     */
    public static function create(): BulkOptimizationOrchestrator
    {
        // Get WordPress upload directory
        $uploadDir = \wp_upload_dir();
        if (! is_array($uploadDir) || empty($uploadDir['basedir'])) {
            $uploadDir = [ 'basedir' => '' ];
        }

        $basedir = is_string($uploadDir['basedir']) ? $uploadDir['basedir'] : '';

        // Create dependencies
        $factory = new class () implements \ImageOptimizer\Interfaces\ImageEditorFactoryInterface {
            /**
             * Get WordPress image editor
             *
             * @param string $filePath File path.
             *
             * @return \WP_Image_Editor|\WP_Error WordPress image editor or error.
             */
            public function get(string $filePath)
            {
                // Use WordPress's native wp_get_image_editor function
                $editor = \wp_get_image_editor($filePath);
                return $editor;
            }
        };

        $converter = new WebPConverter();
        $processor = new ImageFileProcessor($factory);
        $manager = new MetadataManager();
        $migrator = new MetadataMigrator($manager);

        return new BulkOptimizationOrchestrator(
            $processor,
            $migrator,
            $basedir,
        );
    }
}
