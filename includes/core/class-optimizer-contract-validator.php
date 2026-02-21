<?php

declare(strict_types=1);

/**
 * Optimizer Contract Validator
 *
 * Validates that custom optimizer implementations follow the LSP contract.
 * Prevents regressions by detecting inconsistent return types or missing methods.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Contract validator for optimizer implementations
 */
class OptimizerContractValidator
{
    /**
     * Validate that an optimizer implements the required contract
     *
     * @param object $optimizer The optimizer instance to validate.
     * @return array Validation result with 'valid' and 'errors' keys.
     */
    public static function validate($optimizer): array
    {
        $errors = [];

        // Check if it's an OptimizerInterface instance
        if (! $optimizer instanceof OptimizerInterface) {
            $errors[] = 'Optimizer must implement OptimizerInterface';
        }

        // Check method signatures
        $errors = array_merge($errors, self::validate_method_signatures($optimizer));

        // Check return types (runtime validation)
        $errors = array_merge($errors, self::validate_return_types($optimizer));

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate method signatures
     *
     * @param object $optimizer The optimizer instance.
     * @return array Array of errors.
     */
    private static function validate_method_signatures($optimizer): array
    {
        $errors = [];

        if (! method_exists($optimizer, 'optimize_attachment')) {
            $errors[] = 'Missing method: optimize_attachment(int): Result';
        }

        if (! method_exists($optimizer, 'revert_optimization')) {
            $errors[] = 'Missing method: revert_optimization(int): Result';
        }

        return $errors;
    }

    /**
     * Validate return types
     *
     * @param object $optimizer The optimizer instance.
     * @return array Array of errors.
     */
    private static function validate_return_types($optimizer): array
    {
        $errors = [];

        // Check optimize_attachment return type
        if (method_exists($optimizer, 'optimize_attachment')) {
            $reflection = new \ReflectionMethod($optimizer, 'optimize_attachment');
            $return_type = $reflection->getReturnType();

            if (! $return_type) {
                $errors[] = 'optimize_attachment() must have explicit return type : Result';
            } elseif ((string) $return_type !== Result::class) {
                $errors[] = sprintf(
                    'optimize_attachment() must return Result, got: %s',
                    (string) $return_type
                );
            }
        }

        // Check revert_optimization return type
        if (method_exists($optimizer, 'revert_optimization')) {
            $reflection = new \ReflectionMethod($optimizer, 'revert_optimization');
            $return_type = $reflection->getReturnType();

            if (! $return_type) {
                $errors[] = 'revert_optimization() must have explicit return type : Result';
            } elseif ((string) $return_type !== Result::class) {
                $errors[] = sprintf(
                    'revert_optimization() must return Result, got: %s',
                    (string) $return_type
                );
            }
        }

        return $errors;
    }

    /**
     * Test hook to ensure it returns Result
     *
     * Useful for testing custom optimizer implementations.
     *
     * @param OptimizerInterface $optimizer The optimizer to test.
     * @param int                $attachment_id A valid attachment ID.
     * @return array Result of the test with 'passed', 'result_type', and 'message'.
     */
    public static function test_hook(OptimizerInterface $optimizer, int $attachment_id): array
    {
        try {
            $result = $optimizer->optimize_attachment($attachment_id);

            // Verify it's a Result instance
            if (! $result instanceof Result) {
                return [
                    'passed'      => false,
                    'result_type' => get_class($result),
                    'message'     => 'Hook returned wrong type: expected Result, got ' . get_class($result),
                ];
            }

            return [
                'passed'      => true,
                'result_type' => 'Result',
                'message'     => 'Hook returned correct Result object',
            ];
        } catch (\Exception $e) {
            return [
                'passed'      => false,
                'result_type' => 'Exception',
                'message'     => 'Hook threw exception: ' . $e->getMessage(),
            ];
        }
    }
}
