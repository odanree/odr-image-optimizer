<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access denied.' );
}

declare(strict_types=1);

/**
 * Exception thrown when backup operations fail
 *
 * @package ImageOptimizer\Exception
 */

namespace ImageOptimizer\Exception;

class BackupFailedException extends \Exception
{
    public function __construct(
        string $message = 'Backup operation failed',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
