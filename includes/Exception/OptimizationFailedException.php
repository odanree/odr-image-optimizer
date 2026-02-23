<?php

declare(strict_types=1);

/**
 * Exception thrown when image optimization fails
 *
 * Extends ImageOptimizerException for proper exception hierarchy.
 * Ensures all processor implementations throw exceptions matching the interface contract.
 *
 * @package ImageOptimizer\Exception
 */

namespace ImageOptimizer\Exception;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Thrown when image processing fails (e.g., format conversion, GD errors)
 *
 * Ensures Liskov Substitution Principle: all ImageProcessorInterface implementations
 * catch and rethrow as OptimizationFailedException, providing consistent exception type.
 */
class OptimizationFailedException extends ImageOptimizerException
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
        string $message = 'Image optimization failed',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
