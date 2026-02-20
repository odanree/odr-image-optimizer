<?php

declare(strict_types=1);
if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Optimization Engine Factory
 *
 * @package ImageOptimizer\Factory
 */

namespace ImageOptimizer\Factory;

use ImageOptimizer\Backup\BackupManager;
use ImageOptimizer\Core\OptimizationEngine;
use ImageOptimizer\Conversion\WebpConverter;
use ImageOptimizer\Processor\ImageProcessorInterface;
use ImageOptimizer\Processor\ProcessorRegistry;
use ImageOptimizer\Repository\DatabaseRepository;

class OptimizationEngineFactory
{
    /**
     * Create an OptimizationEngine with default processors and WebP converter
     *
     * @return OptimizationEngine
     */
    public static function create(): OptimizationEngine
    {
        global $wpdb;

        return new OptimizationEngine(
            new BackupManager('.backups'),
            new DatabaseRepository($wpdb),
            ProcessorRegistry::default(),
            new WebpConverter(),
        );
    }

    /**
     * Create with custom backup directory
     *
     * @param string $backupDir
     * @return OptimizationEngine
     */
    public static function createWithBackupDir(string $backupDir): OptimizationEngine
    {
        global $wpdb;

        return new OptimizationEngine(
            new BackupManager($backupDir),
            new DatabaseRepository($wpdb),
            ProcessorRegistry::default(),
            new WebpConverter(),
        );
    }

    /**
     * Create with custom ProcessorRegistry (Morph Map)
     *
     * @param array<string, class-string<ImageProcessorInterface>> $mimeTypeMap MIME type → Processor class
     * @return OptimizationEngine
     */
    public static function createWithProcessors(array $mimeTypeMap): OptimizationEngine
    {
        global $wpdb;

        return new OptimizationEngine(
            new BackupManager('.backups'),
            new DatabaseRepository($wpdb),
            ProcessorRegistry::fromMorphMap($mimeTypeMap),
            new WebpConverter(),
        );
    }

    /**
     * Create with fully custom configuration
     *
     * @param ProcessorRegistry $registry
     * @param BackupManager|null $backupManager
     * @param WebpConverter|null $webpConverter
     * @return OptimizationEngine
     */
    public static function createCustom(
        ProcessorRegistry $registry,
        ?BackupManager $backupManager = null,
        ?WebpConverter $webpConverter = null,
    ): OptimizationEngine {
        global $wpdb;

        return new OptimizationEngine(
            $backupManager ?? new BackupManager('.backups'),
            new DatabaseRepository($wpdb),
            $registry,
            $webpConverter ?? new WebpConverter(),
        );
    }
}
