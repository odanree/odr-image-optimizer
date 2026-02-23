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
     * NUCLEAR OPTION: Iterate directly with reference (&) to mutate array in-place
     * and make a single database update (not one per size).
     *
     * @param int    $attachmentId The attachment ID.
     * @param string $uploadDir    Full path to uploads directory.
     *
     * @return int Number of sizes migrated.
     */
    public function migrate_all_sizes(int $attachmentId, string $uploadDir): int
    {
        // Get metadata from database
        $metadata = $this->manager->getMetadata($attachmentId);
        if (! is_array($metadata)) {
            return 0;
        }

        // Validate file key exists
        if (! isset($metadata['file']) || ! is_string($metadata['file'])) {
            return 0;
        }

        // Validate sizes array exists
        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return 0;
        }

        $upload_dir_info = wp_upload_dir();
        $upload_base = $upload_dir_info['basedir'];
        $relative_path = dirname($metadata['file']);

        $count = 0;

        // NUCLEAR OPTION: Iterate directly over $metadata['sizes'] with reference (&)
        // This mutates the actual metadata array, not a copy
        foreach ($metadata['sizes'] as $slug => &$size_data) {
            // Skip if not an array
            if (! is_array($size_data)) {
                continue;
            }

            // Get the current JPG filename
            $jpg_file = $size_data['file'] ?? '';
            if (! is_string($jpg_file) || empty($jpg_file)) {
                continue;
            }

            // Determine target WebP filename
            $webp_filename = str_replace(
                [ '.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG' ],
                '.webp',
                $jpg_file,
            );

            // Build full path to WebP file
            $full_path = $upload_base . '/' . $relative_path . '/' . $webp_filename;

            // Only migrate if WebP actually exists on disk
            if (file_exists($full_path)) {
                // Hard-update the metadata strings
                $size_data['file'] = $webp_filename;
                $size_data['mime-type'] = 'image/webp';
                $size_data['filesize'] = (int) filesize($full_path);

                $count++;
            }
        }

        // Break the reference to avoid accidental mutations
        unset($size_data);

        // Also update the primary 'file' key
        // WordPress uses this as source path for all size calculations
        $metadata['file'] = str_replace(
            [ '.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG' ],
            '.webp',
            $metadata['file'],
        );

        // Single database update with all transformed metadata
        // All mutations from the foreach loop persist because we iterated with reference (&)
        $updated = wp_update_attachment_metadata($attachmentId, $metadata);

        // Verify update was successful
        if ($updated === false) {
            error_log(
                sprintf(
                    'Failed to update metadata for attachment %d during migrate_all_sizes. Result: %s',
                    $attachmentId,
                    var_export($updated, true),
                ),
            );
            return 0;
        }

        return $count;
    }

    /**
     * Migrate sizes from ConversionResult batch
     *
     * After optimization, WebP files exist on disk but metadata still references JPG.
     * This method sweeps ALL sizes and updates them if WebP versions exist.
     *
     * CRITICAL: Iterate directly over metadata['sizes'] with reference (&)
     * to ensure mutations persist in actual metadata array.
     *
     * @param int                              $attachmentId The attachment ID.
     * @param array<string, ConversionResult> $results      Array of size_name => ConversionResult pairs.
     *
     * @return int Number of sizes successfully migrated.
     */
    public function migrate_from_results(int $attachmentId, array $results): int
    {
        // Get metadata from database
        $metadata = $this->manager->getMetadata($attachmentId);
        if (! is_array($metadata)) {
            return 0;
        }

        // Validate file key exists
        if (! isset($metadata['file']) || ! is_string($metadata['file'])) {
            return 0;
        }

        // Validate sizes array exists
        if (! isset($metadata['sizes']) || ! is_array($metadata['sizes'])) {
            return 0;
        }

        $upload_dir_info = wp_upload_dir();
        $upload_base = $upload_dir_info['basedir'];
        $relative_path = dirname($metadata['file']);

        $count = 0;

        // CRITICAL FIX: Iterate directly over metadata['sizes'] with reference (&)
        // This mutates the actual metadata array, not a copy
        // Don't iterate over $results - sweep ALL sizes in metadata
        foreach ($metadata['sizes'] as $slug => &$size_data) {
            // Skip if not an array
            if (! is_array($size_data)) {
                continue;
            }

            // Get the current JPG filename
            $jpg_file = $size_data['file'] ?? '';
            if (! is_string($jpg_file) || empty($jpg_file)) {
                continue;
            }

            // Determine target WebP filename
            $webp_filename = str_replace(
                [ '.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG' ],
                '.webp',
                $jpg_file,
            );

            // Build full path to WebP file
            $full_path = $upload_base . '/' . $relative_path . '/' . $webp_filename;

            // Only migrate if WebP actually exists on disk
            if (file_exists($full_path)) {
                // Hard-update the metadata strings
                $size_data['file'] = $webp_filename;
                $size_data['mime-type'] = 'image/webp';
                $size_data['filesize'] = (int) filesize($full_path);

                $count++;
            }
        }

        // Break the reference to avoid accidental mutations
        unset($size_data);

        // Also update the primary 'file' key
        // WordPress uses this as source path for all size calculations
        $metadata['file'] = str_replace(
            [ '.jpg', '.jpeg', '.png', '.JPG', '.JPEG', '.PNG' ],
            '.webp',
            $metadata['file'],
        );

        // Single database update with all transformed metadata
        // All mutations from the foreach loop persist because we iterated with reference (&)
        $updated = wp_update_attachment_metadata($attachmentId, $metadata);

        // Verify update was successful
        if ($updated === false) {
            error_log(
                sprintf(
                    'Failed to update metadata for attachment %d during migrate_from_results. Result: %s',
                    $attachmentId,
                    var_export($updated, true),
                ),
            );
            return 0;
        }

        return $count;
    }
}
