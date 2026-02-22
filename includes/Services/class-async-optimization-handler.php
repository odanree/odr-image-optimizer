<?php

declare(strict_types=1);

/**
 * Async Optimization Handler
 *
 * Processes image optimization in background via Action Scheduler.
 * Coordinates orchestrator lifecycle for individual attachments.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Async optimization handler
 *
 * Registers and handles async optimization actions.
 * Each background job optimizes a single attachment atomically.
 */
class AsyncOptimizationHandler
{
    /**
     * Orchestrator instance
     *
     * @var BulkOptimizationOrchestrator
     */
    private BulkOptimizationOrchestrator $orchestrator;

    /**
     * Constructor
     *
     * @param BulkOptimizationOrchestrator $orchestrator The orchestrator instance.
     */
    public function __construct(BulkOptimizationOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Register async action hooks
     *
     * @return void
     */
    public function register(): void
    {
        \add_action(
            'odr_optimize_single_image',
            [ $this, 'handle_optimization' ],
            10,
            1,
        );
    }

    /**
     * Handle single image optimization
     *
     * Called by Action Scheduler in background process.
     * Runs orchestrator for single attachment (atomic unit).
     *
     * @param int $attachmentId The attachment ID to optimize.
     *
     * @return bool True if optimization completed.
     */
    public function handle_optimization(int $attachmentId): bool
    {
        // Create converter for this optimization
        $converter = new WebPConverter();

        // Run orchestrator atomically for this attachment
        return $this->orchestrator->optimize($attachmentId, $converter);
    }

    /**
     * Enqueue optimization for an attachment
     *
     * Queues a background optimization job via Action Scheduler.
     * Returns immediately without blocking.
     *
     * @param int $attachmentId The attachment ID to optimize.
     *
     * @return bool True if enqueued successfully.
     */
    public static function enqueue(int $attachmentId): bool
    {
        // Check if Action Scheduler is available
        if (! \function_exists('as_enqueue_async_action')) {
            return false;
        }

        try {
            \as_enqueue_async_action(
                'odr_optimize_single_image',
                [ 'id' => $attachmentId ],
                'odr-image-optimizer',
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
