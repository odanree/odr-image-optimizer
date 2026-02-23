<?php

declare(strict_types=1);

/**
 * Exception thrown when processor is not available
 *
 * Used when required dependencies (GD extension, etc.) are not available.
 *
 * @package ImageOptimizer\Exception
 */

namespace ImageOptimizer\Exception;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Thrown when processor dependencies are unavailable
 *
 * Examples:
 * - GD extension not loaded
 * - WebP support not available in GD
 * - Required system library missing
 *
 * This allows callers to detect missing dependencies and fall back to alternative processors.
 */
class ProcessorNotAvailableException extends ImageOptimizerException
{
    /**
     * The missing dependency name
     *
     * @var string
     */
    private string $dependency;

    /**
     * Constructor specifying missing dependency
     *
     * @param string          $dependency The name of the missing dependency.
     * @param string          $message The error message.
     * @param int             $code The error code.
     * @param \Throwable|null $previous Previous exception for chaining.
     */
    public function __construct(
        string $dependency,
        string $message = 'Processor dependency not available',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $this->dependency = $dependency;
        parent::__construct($message, $code, $previous, ['dependency' => $dependency]);
    }

    /**
     * Get the missing dependency name
     *
     * @return string The dependency name.
     */
    public function get_dependency(): string
    {
        return $this->dependency;
    }
}
