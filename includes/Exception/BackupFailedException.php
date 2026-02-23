<?php

declare(strict_types=1);

/**
 * Exception thrown when backup operations fail
 *
 * Extends ImageOptimizerException for proper exception hierarchy.
 * Separates backup failures from optimization failures, allowing targeted error handling.
 *
 * @package ImageOptimizer\Exception
 */

namespace ImageOptimizer\Exception;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Thrown when backup or restore operations fail
 *
 * Separate from OptimizationFailedException to allow different error handling strategies:
 * - OptimizationFailedException: User can retry or skip image
 * - BackupFailedException: Abort operation to prevent data loss
 */
class BackupFailedException extends ImageOptimizerException
{
    /**
     * Constructor with exception chaining support
     *
     * @param string          $message The error message.
     * @param int             $code The error code.
     * @param \Throwable|null $previous Previous exception for chaining.
     * @param array           $context Additional context data.
     */
    public function __construct(
        string $message = 'Backup operation failed',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
