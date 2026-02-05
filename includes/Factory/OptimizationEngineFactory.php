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

        $processors = [
            new JpegProcessor(),
            new PngProcessor(),
            new WebpProcessor(),
        ];

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

        $processors = [
            new JpegProcessor(),
            new PngProcessor(),
            new WebpProcessor(),
        ];

        return new OptimizationEngine($backupManager, $repository, $processors);
    }

    /**
     * Create with custom processors
     *
     * @param array $processors
     * @return OptimizationEngine
     */
    public static function createWithProcessors(array $processors): OptimizationEngine
    {
        global $wpdb;

        $backupManager = new BackupManager('.backups');
        $repository = new DatabaseRepository($wpdb);

        return new OptimizationEngine($backupManager, $repository, $processors);
    }
}
