<?php

declare(strict_types=1);

/**
 * Optimization Engine Factory
 *
 * @package ImageOptimizer\Factory
 */

namespace ImageOptimizer\Factory;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}
use ImageOptimizer\Backup\BackupManager;
use ImageOptimizer\Core\OptimizationEngine;
use ImageOptimizer\Conversion\WebpConverter;
use ImageOptimizer\Processor\ImageProcessorInterface;
use ImageOptimizer\Processor\ProcessorRegistry;
use ImageOptimizer\Repository\DatabaseRepository;

class OptimizationEngineFactory
{
    public static function create(): OptimizationEngine
    {
        global $wpdb;

        return new OptimizationEngine(
            self::defaultBackupManager(),
            new DatabaseRepository($wpdb),
            ProcessorRegistry::default(),
            new WebpConverter(),
        );
    }

    /**
     * Create with a custom backup base directory (absolute path).
     *
     * Used by tests and integrators who need to direct backups outside
     * the default uploads/odr-image-optimizer/backups location.
     */
    public static function createWithBackupDir(string $backupBaseDir): OptimizationEngine
    {
        global $wpdb;

        return new OptimizationEngine(
            new BackupManager($backupBaseDir, self::uploadsBaseDir()),
            new DatabaseRepository($wpdb),
            ProcessorRegistry::default(),
            new WebpConverter(),
        );
    }

    /**
     * @param array<string, class-string<ImageProcessorInterface>> $mimeTypeMap
     */
    public static function createWithProcessors(array $mimeTypeMap): OptimizationEngine
    {
        global $wpdb;

        return new OptimizationEngine(
            self::defaultBackupManager(),
            new DatabaseRepository($wpdb),
            ProcessorRegistry::fromMorphMap($mimeTypeMap),
            new WebpConverter(),
        );
    }

    public static function createCustom(
        ProcessorRegistry $registry,
        ?BackupManager $backupManager = null,
        ?WebpConverter $webpConverter = null,
    ): OptimizationEngine {
        global $wpdb;

        return new OptimizationEngine(
            $backupManager ?? self::defaultBackupManager(),
            new DatabaseRepository($wpdb),
            $registry,
            $webpConverter ?? new WebpConverter(),
        );
    }

    private static function defaultBackupManager(): BackupManager
    {
        $uploadsBase = self::uploadsBaseDir();
        return new BackupManager($uploadsBase . '/odr-image-optimizer/backups', $uploadsBase);
    }

    private static function uploadsBaseDir(): string
    {
        $uploads = wp_upload_dir(null, false);
        if (is_array($uploads) && empty($uploads['error']) && ! empty($uploads['basedir'])) {
            return (string) $uploads['basedir'];
        }
        return WP_CONTENT_DIR . '/uploads';
    }
}
