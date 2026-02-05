<?php
declare(strict_types=1);

/**
 * Optimization Engine Factory
 *
 * @package ImageOptimizer\Factory
 */

namespace ImageOptimizer\Factory;

use ImageOptimizer\Backup\BackupManager;
use ImageOptimizer\Core\OptimizationEngine;
use ImageOptimizer\Processor\ProcessorRegistry;
use ImageOptimizer\Repository\DatabaseRepository;

class OptimizationEngineFactory
{
    /**
     * Create an OptimizationEngine with default processors
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
        );
    }

    /**
     * Create with custom ProcessorRegistry (Morph Map)
     *
     * @param array<string, class-string> $mimeTypeMap MIME type → Processor class
     * @return OptimizationEngine
     */
    public static function createWithProcessors(array $mimeTypeMap): OptimizationEngine
    {
        global $wpdb;

        return new OptimizationEngine(
            new BackupManager('.backups'),
            new DatabaseRepository($wpdb),
            ProcessorRegistry::fromMorphMap($mimeTypeMap),
        );
    }

    /**
     * Create with fully custom configuration
     *
     * @param ProcessorRegistry $registry
     * @param BackupManager|null $backupManager
     * @return OptimizationEngine
     */
    public static function createCustom(
        ProcessorRegistry $registry,
        ?BackupManager $backupManager = null,
    ): OptimizationEngine {
        global $wpdb;

        return new OptimizationEngine(
            $backupManager ?? new BackupManager('.backups'),
            new DatabaseRepository($wpdb),
            $registry,
        );
    }
}
