<?php

declare(strict_types=1);

/**
 * Bulk Optimization Orchestrator
 *
 * Coordinates image optimization across multiple attachments.
 * Implements atomic unit pattern: File + Metadata updated together.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

use ImageOptimizer\Interfaces\ImageConverterInterface;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Bulk optimization orchestrator
 *
 * Orchestrates the optimization flow:
 * 1. Process original image + subsizes → ConversionResult objects
 * 2. Update metadata with WebP entries (synchronize DB with filesystem)
 * 3. Clear object cache to ensure WordPress sees new metadata
 *
 * This is an atomic unit pattern: each attachment fully processed
 * before moving to the next, ensuring crash-safe semantics.
 */
class BulkOptimizationOrchestrator
{
    /**
     * Image file processor
     *
     * @var ImageFileProcessor
     */
    private ImageFileProcessor $processor;

    /**
     * Metadata migrator (DB sync)
     *
     * @var MetadataMigrator
     */
    private MetadataMigrator $migrator;

    /**
     * Upload directory path
     *
     * @var string
     */
    private string $uploadDir;

    /**
     * Constructor
     *
     * @param ImageFileProcessor $processor  File processor for conversion.
     * @param MetadataMigrator   $migrator   Metadata migrator for DB sync.
     * @param string             $uploadDir  Full path to uploads directory.
     */
    public function __construct(
        ImageFileProcessor $processor,
        MetadataMigrator $migrator,
        string $uploadDir,
    ) {
        $this->processor = $processor;
        $this->migrator = $migrator;
        $this->uploadDir = $uploadDir;
    }

    /**
     * Optimize a single attachment
     *
     * Atomic operation:
     * 1. Convert original file
     * 2. Convert all subsizes
     * 3. Update metadata
     * 4. Clear cache
     *
     * @param int                      $attachmentId The attachment ID.
     * @param ImageConverterInterface  $converter    Strategy for conversion.
     *
     * @return bool True if optimization completed (success or handled).
     */
    public function optimize(
        int $attachmentId,
        ImageConverterInterface $converter,
    ): bool {
        // Get attachment file path
        $attachmentPath = \get_attached_file($attachmentId);
        if (! is_string($attachmentPath)) {
            return false;
        }

        // 1. Process original image
        $originalResult = $this->processor->process($attachmentPath, $converter);
        if (! $originalResult->isSuccess()) {
            return false;
        }

        // 2. Process subsizes (thumbnails, medium, large, etc.)
        $subsizeResults = $this->processor->process_subsizes(
            $attachmentId,
            $converter,
            $this->uploadDir,
        );

        // Combine original + subsizes results
        $allResults = [ 'original' => $originalResult ];
        foreach ($subsizeResults as $sizeName => $result) {
            $allResults[ $sizeName ] = $result;
        }

        // 3. Update metadata with all conversion results
        $this->migrator->migrate_from_results($attachmentId, $allResults);

        // 4. Clear object cache - CRITICAL
        // Without this, WordPress continues using old cached metadata
        \clean_attachment_cache($attachmentId);

        return true;
    }

    /**
     * Optimize multiple attachments
     *
     * Batch operation with atomic units.
     * Each attachment is fully processed (File + Meta) before next.
     *
     * If the process crashes at iteration 250/500, you know exactly
     * which 250 are complete (verified by DB metadata + cache clear).
     *
     * @param array<int>                $attachmentIds Array of attachment IDs.
     * @param ImageConverterInterface   $converter     Strategy for conversion.
     *
     * @return array<string, mixed> Statistics: count, succeeded, failed.
     */
    public function optimize_batch(
        array $attachmentIds,
        ImageConverterInterface $converter,
    ): array {
        $count = count($attachmentIds);
        $succeeded = 0;
        $failed = 0;

        foreach ($attachmentIds as $id) {
            if (! is_int($id)) {
                $failed++;
                continue;
            }

            if ($this->optimize($id, $converter)) {
                $succeeded++;
            } else {
                $failed++;
            }
        }

        return [
            'total'     => $count,
            'succeeded' => $succeeded,
            'failed'    => $failed,
        ];
    }
}
