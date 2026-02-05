<?php
declare(strict_types=1);

/**
 * Backup Manager - Handles image backups
 *
 * @package ImageOptimizer\Backup
 */

namespace ImageOptimizer\Backup;

use ImageOptimizer\Exception\BackupFailedException;

readonly class BackupManager
{
    private const BACKUP_SUFFIX = '-backup';

    public function __construct(
        private string $backupDir = '.backups',
    ) {}

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
        if (!file_exists($filePath)) {
            throw new BackupFailedException("Source file not found: {$filePath}");
        }

        $backupPath = $this->getBackupPath($filePath, $identifier);
        $backupDirectory = dirname($backupPath);

        // Create backup directory if it doesn't exist
        if (!is_dir($backupDirectory)) {
            if (!@mkdir($backupDirectory, 0755, true)) {
                throw new BackupFailedException("Failed to create backup directory: {$backupDirectory}");
            }
        }

        // Don't overwrite existing backup
        if (file_exists($backupPath)) {
            return $backupPath;
        }

        if (!@copy($filePath, $backupPath)) {
            throw new BackupFailedException("Failed to copy file to backup: {$filePath}");
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
        $backupPath = $this->getBackupPath($filePath, $identifier);

        if (!file_exists($backupPath)) {
            throw new BackupFailedException("Backup not found: {$backupPath}");
        }

        if (!@copy($backupPath, $filePath)) {
            throw new BackupFailedException("Failed to restore file from backup");
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
        return file_exists($this->getBackupPath($filePath, $identifier));
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
        $backupPath = $this->getBackupPath($filePath, $identifier);

        if (!file_exists($backupPath)) {
            return true; // Already deleted
        }

        return @unlink($backupPath);
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
        $filename = $pathinfo['filename'] . self::BACKUP_SUFFIX . '-' . $identifier . '.' . $pathinfo['extension'];

        return $backupDirectory . '/' . $filename;
    }
}
