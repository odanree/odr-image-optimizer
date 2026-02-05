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
use ImageOptimizer\Processor\JpegProcessor;
use ImageOptimizer\Processor\PngProcessor;
use ImageOptimizer\Processor\ProcessorCollection;
use ImageOptimizer\Processor\ProcessorRegistry;
use ImageOptimizer\Processor\WebpProcessor;
use ImageOptimizer\Repository\DatabaseRepository;

class OptimizationEngineFactory
{
    /**
     * Create an OptimizationEngine with all dependencies
     *
     * @return OptimizationEngine
     */
    public static function create(): OptimizationEngine
    {
        global $wpdb;

        $backupManager = new BackupManager('.backups');
        $repository = new DatabaseRepository($wpdb);

        // Use default processors
        $processors = new ProcessorCollection(
            new JpegProcessor(),
            new PngProcessor(),
            new WebpProcessor(),
        );

        return new OptimizationEngine($backupManager, $repository, $processors);
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

        $backupManager = new BackupManager($backupDir);
        $repository = new DatabaseRepository($wpdb);

        $processors = new ProcessorCollection(
            new JpegProcessor(),
            new PngProcessor(),
            new WebpProcessor(),
        );

        return new OptimizationEngine($backupManager, $repository, $processors);
    }

    /**
     * Create with custom processors via registry (Morph Map pattern)
     *
     * @param ProcessorRegistry $registry
     * @return OptimizationEngine
     */
    public static function createWithRegistry(ProcessorRegistry $registry): OptimizationEngine
    {
        global $wpdb;

        $backupManager = new BackupManager('.backups');
        $repository = new DatabaseRepository($wpdb);

        // Build ProcessorCollection from registry
        $processors = new ProcessorCollection(
            ...array_map(
                fn(string $mimeType) => $registry->create($mimeType),
                $registry->getSupportedMimeTypes()
            )
        );

        return new OptimizationEngine($backupManager, $repository, $processors);
    }

    /**
     * Create with custom registry configuration
     *
     * @param array<string, class-string> $registryMap MIME type → Processor class mapping
     * @return OptimizationEngine
     */
    public static function createWithCustomRegistry(array $registryMap): OptimizationEngine
    {
        $registry = new ProcessorRegistry($registryMap);
        return self::createWithRegistry($registry);
    }
}
