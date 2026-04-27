<?php

declare(strict_types=1);

/**
 * Backup Manager - Handles image backups
 *
 * Uses WordPress Filesystem API for proper permission handling and security.
 *
 * @package ImageOptimizer\Backup
 */

namespace ImageOptimizer\Backup;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}
use ImageOptimizer\Exception\BackupFailedException;

readonly class BackupManager
{
    private const BACKUP_SUFFIX = '-backup';

    public function __construct(
        private string $backupDir = '.backups',
    ) {}

    /**
     * Initialize WordPress Filesystem API
     *
     * @return bool True if filesystem is initialized
     */
    private function init_filesystem(): bool
    {
        if (! function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;
        if (! $wp_filesystem) {
            WP_Filesystem();
        }

        return $wp_filesystem instanceof \WP_Filesystem_Base;
    }

    /**
     * Create a backup of an image file
     *
     * @param string $filePath Path to the original file
     * @param string $identifier Unique identifier for the backup
     * @return string Path to the backup file
     *
     * @throws BackupFailedException
     */
    public function createBackup(string $filePath, string $identifier): string
    {
        if (! $this->init_filesystem()) {
            throw new BackupFailedException('Failed to initialize filesystem');
        }

        global $wp_filesystem;

        if (! $wp_filesystem->exists($filePath)) {
            throw new BackupFailedException("Source file not found: {$filePath}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $backupPath = $this->getBackupPath($filePath, $identifier);
        $backupDirectory = dirname($backupPath);

        // Create backup directory if it doesn't exist
        if (! $wp_filesystem->is_dir($backupDirectory)) {
            if (! $wp_filesystem->mkdir($backupDirectory, 0755)) {
                throw new BackupFailedException("Failed to create backup directory: {$backupDirectory}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
        }

        // Don't overwrite existing backup
        if ($wp_filesystem->exists($backupPath)) {
            return $backupPath;
        }

        if (! $wp_filesystem->copy($filePath, $backupPath)) {
            throw new BackupFailedException("Failed to copy file to backup: {$filePath}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        return $backupPath;
    }

    /**
     * Restore a file from its backup
     *
     * @param string $filePath Path to the file to restore
     * @param string $identifier Unique identifier for the backup
     * @return bool True on success
     *
     * @throws BackupFailedException
     */
    public function restore(string $filePath, string $identifier): bool
    {
        if (! $this->init_filesystem()) {
            throw new BackupFailedException('Failed to initialize filesystem');
        }

        global $wp_filesystem;

        $backupPath = $this->getBackupPath($filePath, $identifier);

        if (! $wp_filesystem->exists($backupPath)) {
            throw new BackupFailedException("Backup not found: {$backupPath}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        if (! $wp_filesystem->copy($backupPath, $filePath)) {
            throw new BackupFailedException('Failed to restore file from backup');
        }

        return true;
    }

    /**
     * Check if a backup exists
     *
     * @param string $filePath Path to the original file
     * @param string $identifier Unique identifier for the backup
     * @return bool
     */
    public function hasBackup(string $filePath, string $identifier): bool
    {
        if (! $this->init_filesystem()) {
            return false;
        }

        global $wp_filesystem;

        return $wp_filesystem->exists($this->getBackupPath($filePath, $identifier));
    }

    /**
     * Delete a backup file
     *
     * @param string $filePath Path to the original file
     * @param string $identifier Unique identifier for the backup
     * @return bool True on success
     */
    public function deleteBackup(string $filePath, string $identifier): bool
    {
        if (! $this->init_filesystem()) {
            return false;
        }

        global $wp_filesystem;

        $backupPath = $this->getBackupPath($filePath, $identifier);

        if (! $wp_filesystem->exists($backupPath)) {
            return true; // Already deleted
        }

        return $wp_filesystem->delete($backupPath);
    }

    /**
     * Get the backup file path
     *
     * @param string $filePath Path to the original file
     * @param string $identifier Unique identifier
     * @return string
     */
    private function getBackupPath(string $filePath, string $identifier): string
    {
        $pathinfo = pathinfo($filePath);
        $backupDirectory = dirname($filePath) . '/' . $this->backupDir;
        $extension = $pathinfo['extension'] ?? '';
        $filename = $pathinfo['filename'] . self::BACKUP_SUFFIX . '-' . $identifier . '.' . $extension;

        return $backupDirectory . '/' . $filename;
    }
}
