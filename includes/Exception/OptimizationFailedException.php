<?php
declare(strict_types=1);

/**
 * Exception thrown when image optimization fails
 *
 * @package ImageOptimizer\Exception
 */

namespace ImageOptimizer\Exception;

class OptimizationFailedException extends \Exception
{
    public function __construct(
        string $message = 'Image optimization failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
