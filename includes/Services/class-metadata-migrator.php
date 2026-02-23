<?php

declare(strict_types=1);

/**
 * Metadata Migrator
 *
 * Utility to migrate existing metadata from JPG to WebP filenames.
 * Used during optimization to ensure metadata points to converted files.
 *
 * @package ImageOptimizer
 */

namespace ImageOptimizer\Services;

use ImageOptimizer\Result\ConversionResult;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Metadata migrator utility
 *
 * Converts metadata size entries from JPG filenames to WebP filenames
 * when WebP versions exist on disk.
 */
class MetadataMigrator
{
    /**
     * Metadata manager for updates
     *
     * @var MetadataManager
     */
    private MetadataManager $manager;

    /**
     * Constructor
     *
     * @param MetadataManager $manager The metadata manager service.
     */
    public function __construct(MetadataManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Migrate main image file to WebP
     *
     * Updates the top-level 'file' key in metadata if WebP version exists.
     * Critical: WordPress and themes may use this as primary source.
     *
     * @param int    $attachmentId The attachment ID.
     * @param string $uploadDir    Full path to uploads directory.
     *
     * @return bool True if migrated, false otherwise.
     */
    public function migrate_main_file(
        int $attachmentId,
        string $uploadDir,
    ): bool {
        $metadata = $this->manager->getMetadata($attachmentId);

        if (! is_array($metadata)) {
            return false;
        }

        // Get current main file
        $jpgFile = $metadata['file'] ?? '';
        if (! is_string($jpgFile) || empty($jpgFile)) {
            return false;
        }

        // Convert filename to WebP
        $webpFile = \str_replace(
            [ '.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG' ],
            '.webp',
            $jpgFile,
        );

        // Check if WebP exists on disk
        $webpPath = $uploadDir . '/' . $webpFile;
        if (! \file_exists($webpPath)) {
            return false;
        }

        // Update metadata with WebP file
        $metadata['file'] = $webpFile;

        // Store updated metadata
        return \wp_update_attachment_metadata($attachmentId, $metadata) !== false;
    }

    /**
     * Migrate a size entry to WebP if file exists
     *
     * Takes an existing JPG size entry and converts it to WebP
     * if the WebP version exists on disk.
     *
     * @param int    $attachmentId The attachment ID.
     * @param string $sizeName     The size name to migrate.
     * @param string $uploadDir    Full path to uploads directory.
     *
     * @return bool True if migrated, false otherwise.
     */
    public function migrate_size_to_webp(
        int $attachmentId,
        string $sizeName,
        string $uploadDir,
    ): bool {
        $metadata = $this->manager->getMetadata($attachmentId);

        if (! is_array($metadata)) {
            return false;
        }

        $sizes = $metadata['sizes'] ?? null;
        if (! is_array($sizes) || ! isset($sizes[ $sizeName ])) {
            return false;
        }

        $sizeData = $sizes[ $sizeName ];

        // Ensure it's an array
        if (! is_array($sizeData)) {
            return false;
        }

        // Get the current filename
        $jpgFile = $sizeData['file'] ?? '';
        if (! is_string($jpgFile) || empty($jpgFile)) {
            return false;
        }

        // Convert filename to WebP
        $webpFile = \str_replace(
            [ '.jpg', '.jpeg', '.png' ],
            '.webp',
            $jpgFile,
        );

        // Check if WebP exists on disk
        $webpPath = $uploadDir . '/' . $webpFile;
        if (! \file_exists($webpPath)) {
            return false;
        }

        // Get width and height from size data
        $width = isset($sizeData['width']) && is_int($sizeData['width']) ? $sizeData['width'] : 0;
        $height = isset($sizeData['height']) && is_int($sizeData['height']) ? $sizeData['height'] : 0;

        // Build ConversionResult from the WebP file
        $result = new ConversionResult(
            success: true,
            outputPath: $webpPath,
            mimeType: 'image/webp',
            error: null,
            dimensions: [
                'width'  => $width,
                'height' => $height,
            ],
        );

        // Update metadata with WebP entry
        return $this->manager->registerSize($attachmentId, $sizeName, $result);
    }

    /**
     * Migrate all sizes for an attachment
     *
     * Converts all size entries from JPG to WebP if WebP files exist.
     * Also migrates main file if WebP version exists.
     *
     * @param int    $attachmentId The attachment ID.
     * @param string $uploadDir    Full path to uploads directory.
     *
     * @return int Number of sizes migrated.
     */
    public function migrate_all_sizes(int $attachmentId, string $uploadDir): int
    {
        $count = 0;

        // First, migrate main file
        if ($this->migrate_main_file($attachmentId, $uploadDir)) {
            $count++;
        }

        // Then migrate all sizes
        $metadata = $this->manager->getMetadata($attachmentId);

        if (! is_array($metadata) || ! isset($metadata['sizes'])) {
            return $count;
        }

        $sizes = $metadata['sizes'];
        if (! is_array($sizes)) {
            return $count;
        }

        foreach (array_keys($sizes) as $sizeName) {
            if (is_string($sizeName) && $this->migrate_size_to_webp($attachmentId, $sizeName, $uploadDir)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Migrate sizes from ConversionResult batch
     *
     * Batch operation used by orchestrator after image processing.
     * AGGRESSIVELY overwrites old JPG size entries with new WebP entries.
     *
     * For each ConversionResult:
     * - Replaces 'file' key: .jpg → .webp
     * - Replaces 'mime-type': image/jpeg → image/webp
     * - Updates 'filesize' with actual WebP file size
     *
     * @param int                              $attachmentId The attachment ID.
     * @param array<string, ConversionResult> $results      Array of size_name => ConversionResult pairs.
     *
     * @return int Number of sizes successfully migrated.
     */
    public function migrate_from_results(int $attachmentId, array $results): int
    {
        if (empty($results)) {
            return 0;
        }

        // Get current metadata (source of truth)
        $metadata = $this->manager->getMetadata($attachmentId);
        if (! is_array($metadata)) {
            return 0;
        }

        $sizes = $metadata['sizes'] ?? [];
        if (! is_array($sizes)) {
            $sizes = [];
        }

        $count = 0;

        // For each ConversionResult, aggressively overwrite the old JPG entry
        foreach ($results as $sizeName => $result) {
            // Validate inputs
            if (! is_string($sizeName)) {
                continue;
            }

            if (! ($result instanceof ConversionResult)) {
                continue;
            }

            if ($result->isFailure()) {
                continue;
            }

            // Build WebP size entry from ConversionResult
            $webpFile = \basename($result->outputPath);
            $sizeEntry = [
                'file'      => $webpFile,                    // e.g., yosemite-unsplash-704x469.webp
                'width'     => $result->getWidth(),
                'height'    => $result->getHeight(),
                'mime-type' => 'image/webp',                 // ALWAYS image/webp (aggressive override)
            ];

            // Add actual filesize from disk
            if (\file_exists($result->outputPath)) {
                $sizeEntry['filesize'] = (int) \filesize($result->outputPath);
            }

            // AGGRESSIVELY overwrite existing size entry
            // This replaces any old JPG entry with new WebP entry
            $sizes[ $sizeName ] = $sizeEntry;
            $count++;
        }

        // Update metadata with all new WebP entries
        $metadata['sizes'] = $sizes;
        \wp_update_attachment_metadata($attachmentId, $metadata);

        return $count;
    }
}
