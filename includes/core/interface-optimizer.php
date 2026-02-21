<?php

declare(strict_types=1);

/**
 * Optimizer Interface - Defines the contract for all optimizers
 *
 * All optimizer implementations MUST follow this contract to satisfy
 * Liskov Substitution Principle (LSP) and ensure consistent behavior.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Interface for image optimizers
 *
 * Ensures all optimizer implementations return consistent types and data structures.
 * Prevents LSP violations (Liskov Substitution Principle) by enforcing contracts.
 */
interface OptimizerInterface
{
    /**
     * Optimize an image
     *
     * MUST return Result object, never boolean/WP_Error/array.
     * This contract ensures substitutability - any implementation
     * can be swapped for another without breaking calling code.
     *
     * @param int $attachment_id The WordPress attachment ID.
     * @return Result Standardized result object (ALWAYS Result, never other types).
     *
     * Contract:
     * - Always return Result instance
     * - Success: Result::success() with data array
     * - Failure: Result::failure() with error message
     * - Exception: Result::from_exception()
     *
     * Data Contract (on success):
     * - 'original_size': int (bytes)
     * - 'optimized_size': int (bytes)
     * - 'compression_ratio': float (percentage)
     * - 'savings': int (bytes)
     *
     * Never return:
     * - boolean
     * - array (use Result instead)
     * - WP_Error
     * - null
     * - string
     */
    public function optimize_attachment(int $attachment_id): Result;

    /**
     * Revert an optimized image
     *
     * MUST return Result object with same contract as optimize_attachment().
     *
     * @param int $attachment_id The WordPress attachment ID.
     * @return Result Standardized result object (ALWAYS Result, never other types).
     *
     * Data Contract (on success):
     * - 'restored_size': int (bytes)
     *
     * Same contract as optimize_attachment() - always Result.
     */
    public function revert_optimization(int $attachment_id): Result;
}
