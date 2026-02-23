<?php

declare(strict_types=1);

/**
 * Base exception for Image Optimizer
 *
 * Provides a root exception class for the plugin's exception hierarchy.
 * All domain-specific exceptions should extend this.
 *
 * @package ImageOptimizer\Exception
 */

namespace ImageOptimizer\Exception;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Base exception for all Image Optimizer errors
 *
 * Part of a strict exception hierarchy:
 * - ImageOptimizerException (base)
 *   - OptimizationFailedException (processor errors)
 *   - BackupFailedException (backup operations)
 *   - ProcessorException (processor-level contract violations)
 *     - UnsupportedFormatException
 *     - ProcessorNotAvailableException
 *
 * This structure ensures:
 * 1. Liskov Substitution: All exceptions match the same interface contract
 * 2. Single Responsibility: Each exception type has one reason to be thrown
 * 3. Open/Closed: New exception types can be added without modifying existing code
 */
class ImageOptimizerException extends \Exception
{
    /**
     * Exception context (additional debugging info)
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Constructor with optional context
     *
     * @param string          $message The error message.
     * @param int             $code The error code.
     * @param \Throwable|null $previous Previous exception for chaining.
     * @param array           $context Additional context data.
     */
    public function __construct(
        string $message = 'Image Optimizer error',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get exception context for debugging
     *
     * @return array Context information.
     */
    public function get_context(): array
    {
        return $this->context;
    }
}
