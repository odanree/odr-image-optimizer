<?php

declare(strict_types=1);

/**
 * Backup Manager - Handles image backups
 *
 * Uses WordPress Filesystem API for proper permission handling and security.
 *
 * Backups are written under a dedicated root (the uploads directory in
 * production) so that nothing is ever written inside the plugin folder,
 * per the WordPress.org plugin guidelines.
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

    /**
     * @param string $backupBaseDir Absolute root for backup files
     *                              (e.g. wp_upload_dir()['basedir'] . '/odr-image-optimizer/backups').
     * @param string $sourceBaseDir Absolute root containing source media
     *                              (e.g. wp_upload_dir()['basedir']). Used to mirror the
     *                              relative path of each file under $backupBaseDir so
     *                              attachments at different uploads subfolders don't collide.
     */
    public function __construct(
        private string $backupBaseDir,
        private string $sourceBaseDir = '',
    ) {}

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

        if (! $wp_filesystem->is_dir($backupDirectory)) {
            if (! wp_mkdir_p($backupDirectory)) {
                throw new BackupFailedException("Failed to create backup directory: {$backupDirectory}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
        }

        if ($wp_filesystem->exists($backupPath)) {
            return $backupPath;
        }

        if (! $wp_filesystem->copy($filePath, $backupPath)) {
            throw new BackupFailedException("Failed to copy file to backup: {$filePath}"); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        return $backupPath;
    }

    /**
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

    public function hasBackup(string $filePath, string $identifier): bool
    {
        if (! $this->init_filesystem()) {
            return false;
        }

        global $wp_filesystem;

        return $wp_filesystem->exists($this->getBackupPath($filePath, $identifier));
    }

    public function deleteBackup(string $filePath, string $identifier): bool
    {
        if (! $this->init_filesystem()) {
            return false;
        }

        global $wp_filesystem;

        $backupPath = $this->getBackupPath($filePath, $identifier);

        if (! $wp_filesystem->exists($backupPath)) {
            return true;
        }

        return $wp_filesystem->delete($backupPath);
    }

    public function getBackupPath(string $filePath, string $identifier): string
    {
        $pathinfo = pathinfo($filePath);
        $extension = $pathinfo['extension'] ?? '';
        $filename = $pathinfo['filename'] . self::BACKUP_SUFFIX . '-' . $identifier
            . ($extension !== '' ? '.' . $extension : '');

        $relative = $this->relativeToSource($pathinfo['dirname'] ?? '');
        $base = rtrim(str_replace('\\', '/', $this->backupBaseDir), '/');

        return $relative === ''
            ? $base . '/' . $filename
            : $base . '/' . $relative . '/' . $filename;
    }

    private function relativeToSource(string $dir): string
    {
        $normalized = str_replace('\\', '/', $dir);

        if ($this->sourceBaseDir === '') {
            return 'external/' . substr(hash('sha1', $normalized), 0, 12);
        }

        $base = rtrim(str_replace('\\', '/', $this->sourceBaseDir), '/');

        if ($normalized === $base) {
            return '';
        }

        if (str_starts_with($normalized, $base . '/')) {
            return substr($normalized, strlen($base) + 1);
        }

        return 'external/' . substr(hash('sha1', $normalized), 0, 12);
    }
}
