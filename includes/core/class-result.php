<?php

declare(strict_types=1);

/**
 * Optimization Result Value Object
 *
 * Standardizes success/failure responses across all optimizer implementations.
 * Prevents LSP (Liskov Substitution Principle) violations from inconsistent return types.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Result value object for optimization operations
 *
 * All optimizer methods return this standardized Result object,
 * ensuring consistent behavior regardless of implementation.
 *
 * @property-read bool $success Whether operation succeeded
 * @property-read string $message Human-readable message
 * @property-read array $data Operation-specific data
 */
class Result
{
    /**
     * Success flag
     *
     * @var bool
     */
    private $success;

    /**
     * Human-readable message
     *
     * @var string
     */
    private $message;

    /**
     * Operation-specific data
     *
     * @var array
     */
    private $data;

    /**
     * Private constructor - use static factory methods
     *
     * @param bool   $success Success flag.
     * @param string $message Message.
     * @param array  $data Additional data.
     */
    private function __construct(bool $success, string $message = '', array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Create a successful result
     *
     * @param array  $data Operation-specific data.
     * @param string $message Optional success message.
     * @return self
     */
    public static function success(array $data = [], string $message = ''): self
    {
        return new self(true, $message ?: 'Operation completed successfully', $data);
    }

    /**
     * Create a failure result
     *
     * @param string $error_message The error message.
     * @param array  $data Optional error-specific data.
     * @return self
     */
    public static function failure(string $error_message, array $data = []): self
    {
        return new self(false, $error_message, $data);
    }

    /**
     * Create a result from exception
     *
     * @param \Exception $exception The exception.
     * @param array      $data Optional additional data.
     * @return self
     */
    public static function from_exception(\Exception $exception, array $data = []): self
    {
        return new self(
            false,
            'Exception: ' . $exception->getMessage() . ' (Line: ' . $exception->getLine() . ')',
            array_merge($data, ['exception_code' => $exception->getCode()]),
        );
    }

    /**
     * Check if result is successful
     *
     * @return bool
     */
    public function is_success(): bool
    {
        return $this->success;
    }

    /**
     * Get success status
     *
     * @return bool
     */
    public function is_failure(): bool
    {
        return ! $this->success;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function get_message(): string
    {
        return $this->message;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function get_data(): array
    {
        return $this->data;
    }

    /**
     * Get a specific data value
     *
     * @param string $key The key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Convert to array for JSON response
     *
     * @return array
     */
    public function to_array(): array
    {
        // Flatten data fields to top level for API responses
        $response = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        // Merge data fields into top level
        if (is_array($this->data) && ! empty($this->data)) {
            $response = array_merge($response, $this->data);
        }

        return $response;
    }

    /**
     * Convert to WP_Error if failure
     *
     * @return \WP_Error|null WP_Error if failure, null if success.
     */
    public function to_wp_error(): ?\WP_Error
    {
        if ($this->is_success()) {
            return null;
        }

        return new \WP_Error(
            'optimization_failed',
            $this->message,
            $this->data,
        );
    }

    /**
     * Magic getter for property access
     *
     * @param string $name Property name.
     * @return mixed
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'success':
                return $this->success;
            case 'message':
                return $this->message;
            case 'data':
                return $this->data;
            default:
                throw new \LogicException("Undefined property: $name");
        }
    }

    /**
     * Prevent modification
     *
     * @param string $name Property name.
     * @param mixed  $value Value.
     *
     * @throws \LogicException
     */
    public function __set(string $name, $value)
    {
        throw new \LogicException('Result is immutable');
    }
}
