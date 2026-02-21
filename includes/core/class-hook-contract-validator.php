<?php

declare(strict_types=1);

/**
 * Hook Contract Validator
 *
 * Validates that all hooked functions return consistent data structures.
 * Prevents LSP violations where different hooks return different types.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Validates hook implementations follow standardized contracts
 */
class HookContractValidator
{
    /**
     * Standard optimizer hook response structure
     *
     * @var array
     */
    const OPTIMIZER_RESPONSE_SCHEMA = [
        'success'            => 'boolean',
        'path'               => 'string|null',
        'original_size'      => 'int',
        'optimized_size'     => 'int',
        'compression_ratio'  => 'float',
        'savings'            => 'int',
        'error'              => 'string|null',
    ];

    /**
     * Validate hook return value matches contract
     *
     * @param mixed  $return_value The value returned from the hook.
     * @param string $hook_name    The hook name for error reporting.
     * @return array Validation result with 'valid' and 'errors' keys.
     */
    public static function validate_optimizer_response($return_value, string $hook_name): array
    {
        $errors = [];

        // Must be an array or Result object
        if (! is_array($return_value) && ! $return_value instanceof Result) {
            $errors[] = sprintf(
                '%s hook returned %s, expected array or Result object',
                $hook_name,
                gettype($return_value)
            );
            return [
                'valid'  => false,
                'errors' => $errors,
            ];
        }

        // If it's a Result, extract the data field for validation
        if ($return_value instanceof Result) {
            if (! $return_value->is_success()) {
                // Failure results don't need all fields
                return [
                    'valid'  => true,
                    'errors' => [],
                ];
            }
            
            // Extract data from Result
            $data = $return_value->get_data();
            if (! is_array($data)) {
                $errors[] = sprintf(
                    '%s: Result::get_data() returned %s, expected array',
                    $hook_name,
                    gettype($data)
                );
                return [
                    'valid'  => false,
                    'errors' => $errors,
                ];
            }
            
            $return_value = $data;
        }

        // Validate required fields exist
        $required_fields = [ 'original_size', 'optimized_size', 'compression_ratio', 'savings' ];
        foreach ($required_fields as $field) {
            if (! isset($return_value[$field])) {
                $errors[] = sprintf(
                    '%s: Missing required field "%s"',
                    $hook_name,
                    $field
                );
            }
        }

        // Validate field types
        if (isset($return_value['original_size']) && ! is_int($return_value['original_size'])) {
            $errors[] = sprintf(
                '%s: Field "original_size" must be int, got %s',
                $hook_name,
                gettype($return_value['original_size'])
            );
        }

        if (isset($return_value['optimized_size']) && ! is_int($return_value['optimized_size'])) {
            $errors[] = sprintf(
                '%s: Field "optimized_size" must be int, got %s',
                $hook_name,
                gettype($return_value['optimized_size'])
            );
        }

        if (isset($return_value['compression_ratio']) && ! is_numeric($return_value['compression_ratio'])) {
            $errors[] = sprintf(
                '%s: Field "compression_ratio" must be numeric (int or float), got %s',
                $hook_name,
                gettype($return_value['compression_ratio'])
            );
        }

        if (isset($return_value['savings']) && ! is_int($return_value['savings'])) {
            $errors[] = sprintf(
                '%s: Field "savings" must be int, got %s',
                $hook_name,
                gettype($return_value['savings'])
            );
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate WebP conversion hook return value
     *
     * @param mixed  $return_value The value returned from the hook.
     * @param string $hook_name    The hook name for error reporting.
     * @return array Validation result.
     */
    public static function validate_webp_response($return_value, string $hook_name): array
    {
        $errors = [];

        // Must be string (file path) or false (skip WebP)
        if (! is_string($return_value) && $return_value !== false) {
            $errors[] = sprintf(
                '%s hook returned %s, expected string (path) or false (skip)',
                $hook_name,
                gettype($return_value)
            );
        }

        // If string, should be a valid file path
        if (is_string($return_value) && ! file_exists($return_value)) {
            $errors[] = sprintf(
                '%s: Path "%s" does not exist',
                $hook_name,
                $return_value
            );
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Generate validation report
     *
     * @param array  $validations Array of validation results.
     * @return string Human-readable report.
     */
    public static function generate_report(array $validations): string
    {
        $report = "=== Hook Contract Validation Report ===\n\n";
        $total = count($validations);
        $passed = count(array_filter($validations, fn($v) => $v['valid']));
        $failed = $total - $passed;

        $report .= "Total Hooks: $total\n";
        $report .= "Passed: $passed ✅\n";
        $report .= "Failed: $failed " . ($failed > 0 ? "❌\n" : "✅\n") . "\n";

        if ($failed > 0) {
            $report .= "Failures:\n\n";
            foreach ($validations as $hook => $result) {
                if (! $result['valid']) {
                    $report .= "❌ $hook\n";
                    foreach ($result['errors'] as $error) {
                        $report .= "   - $error\n";
                    }
                    $report .= "\n";
                }
            }
        }

        return $report;
    }
}
