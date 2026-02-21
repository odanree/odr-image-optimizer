<?php

declare(strict_types=1);

/**
 * Permissions Manager
 *
 * Handles all permission-related operations for the plugin.
 * Separated to follow Single Responsibility Principle.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Permissions manager class
 */
class PermissionsManager
{
    /**
     * Check if current user can manage image optimization
     *
     * @return bool True if user has admin capability.
     */
    public function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Check if current user can edit a specific attachment
     *
     * @param int $attachment_id The attachment ID.
     * @return bool True if user can edit the attachment.
     */
    public function can_edit_attachment(int $attachment_id): bool
    {
        return current_user_can('edit_post', $attachment_id);
    }

    /**
     * Ensure uploads directory has correct permissions
     *
     * Automatically fixes permission issues that occur in Docker/cloud environments.
     * This is called on plugin activation and before critical operations.
     *
     * @return bool True if directory is writable or was fixed, false if unrecoverable.
     */
    public function ensure_uploads_permissions(): bool
    {
        $uploads_dir = wp_upload_dir();
        $base_dir = $uploads_dir['basedir'];

        if (! is_dir($base_dir)) {
            return false;
        }

        // Already writable, nothing to do
        if (is_writable($base_dir)) {
            return true;
        }

        // Try to make it writable
        @chmod($base_dir, 0775);

        // Also ensure the parent directory is writable
        $parent = dirname($base_dir);
        if (is_dir($parent) && ! is_writable($parent)) {
            @chmod($parent, 0775);
        }

        return is_writable($base_dir);
    }
}
