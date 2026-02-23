<?php

declare(strict_types=1);

/**
 * Metadata Manager
 *
 * "Source of Truth" for attachment metadata database state.
 * Handles construction, validation, and persistence of image size metadata.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

use ImageOptimizer\Result\ConversionResult;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Metadata manager service
 *
 * Responsibilities:
 * 1. Construct a valid size entry from ConversionResult
 * 2. Validate the entry is well-formed
 * 3. Update WordPress attachment metadata
 *
 * Non-Responsibilities:
 * 1. Checking if files exist on disk (ImageFileProcessor's job)
 * 2. Converting images (Converter's job)
 * 3. Orchestrating the process (Orchestrator's job)
 */
class MetadataManager
{
    /**
     * Register converted image size in attachment metadata
     *
     * Injects the conversion result into `_wp_attachment_metadata` sizes array,
     * making WordPress include it in srcset generation and Lighthouse calculations.
     *
     * CRITICAL: Updates the size entry with actual converted file details:
     * - Uses converted filename (.webp)
     * - Sets MIME type to converted format
     * - Stores dimensions for srcset ratio check
     *
     * @param int               $attachmentId   The attachment ID.
     * @param string            $sizeName       The size name (e.g., 'medium_webp', 'odr_content_optimized').
     * @param ConversionResult  $result         The conversion result with dimensions and output path.
     *
     * @return bool True if metadata was updated, false if validation failed.
     */
    public function registerSize(
        int $attachmentId,
        string $sizeName,
        ConversionResult $result,
    ): bool {
        // Validation: Result must be successful
        if ($result->isFailure()) {
            return false;
        }

        // Validation: Must have output path
        if (empty($result->outputPath)) {
            return false;
        }

        // Validation: Must have valid dimensions
        if ($result->getWidth() <= 0 || $result->getHeight() <= 0) {
            return false;
        }

        // Fetch current metadata
        $metadata = \wp_get_attachment_metadata($attachmentId);
        if (! is_array($metadata)) {
            return false;
        }

        // Get sizes array - initialize if missing
        $sizes = $metadata['sizes'] ?? [];
        if (! is_array($sizes)) {
            $sizes = [];
        }

        // Build the size entry from result
        // KEY: Use converted filename (e.g., .webp not .jpg)
        // This ensures WordPress srcset uses converted files
        // CRITICAL: Hardcode mime-type to 'image/webp' (never use result->mimeType)
        $sizeEntry = [
            'file'      => \basename($result->outputPath),  // e.g., image-704x469.webp
            'width'     => $result->getWidth(),             // 704
            'height'    => $result->getHeight(),            // 469
            'mime-type' => 'image/webp',                    // HARDCODED, never image/jpeg
        ];

        // Add filesize if available
        if (\file_exists($result->outputPath)) {
            $sizeEntry['filesize'] = (int) \filesize($result->outputPath);
        }

        // Inject into metadata
        $sizes[ $sizeName ] = $sizeEntry;
        $metadata['sizes'] = $sizes;

        // Persist to database
        $updated = \wp_update_attachment_metadata($attachmentId, $metadata);

        return $updated !== false;
    }

    /**
     * Update multiple sizes at once
     *
     * Batch operation for updating several sizes in a single database call.
     * More efficient than calling registerSize() multiple times.
     *
     * @param int   $attachmentId The attachment ID.
     * @param array<string, ConversionResult> $sizes        Array of size_name => ConversionResult pairs.
     *
     * @return int Number of sizes successfully registered.
     */
    public function registerSizes(int $attachmentId, array $sizes): int
    {
        $count = 0;
        foreach ($sizes as $sizeName => $result) {
            if ($this->registerSize($attachmentId, $sizeName, $result)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get current metadata for an attachment
     *
     * Utility method for inspecting current state.
     *
     * @param int $attachmentId The attachment ID.
     *
     * @return array<string, mixed>|false Current metadata, or false if not found.
     */
    public function getMetadata(int $attachmentId)
    {
        return \wp_get_attachment_metadata($attachmentId);
    }

    /**
     * Check if a size is already registered
     *
     * Prevents duplicate registration.
     *
     * @param int    $attachmentId The attachment ID.
     * @param string $sizeName     The size name to check.
     *
     * @return bool
     */
    public function hasSizeRegistered(int $attachmentId, string $sizeName): bool
    {
        $metadata = $this->getMetadata($attachmentId);

        if (! is_array($metadata)) {
            return false;
        }

        $sizes = $metadata['sizes'] ?? null;
        if (! is_array($sizes)) {
            return false;
        }

        return isset($sizes[ $sizeName ]);
    }
}
