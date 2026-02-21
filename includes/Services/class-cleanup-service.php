<?php

declare(strict_types=1);

/**
 * Cleanup Service
 *
 * Removes unnecessary WordPress scripts and styles to free up the main thread
 * and reduce rendering delays.
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Services;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Debloats WordPress by removing non-critical functionality
 *
 * Removes:
 * - Emoji detection JS (rarely used, blocks rendering)
 * - Redundant lazy-load libraries (native loading="lazy" is better)
 *
 * Impact: Reduces main thread work, frees 30-50ms on 4G
 */
class CleanupService
{
    /**
     * Remove unnecessary WordPress scripts
     *
     * Called at wp_enqueue_scripts (priority 100 = very late, after all enqueues).
     * This ensures our dequeue happens after all plugins have queued their scripts.
     *
     * @return void
     */
    public function remove_bloat(): void
    {
        // Only on frontend
        if (is_admin()) {
            return;
        }

        // Remove emoji detection (not needed for most sites)
        // Emoji JS blocks rendering on low-end devices (saves ~20ms)
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');

        // Dequeue lazy-load scripts (WordPress 5.5+, now redundant with native loading="lazy")
        // Each dequeued script saves HTTP request + parse time (saves ~30ms per script)
        wp_dequeue_script('wp-lazy-load');
        wp_dequeue_script('lazy-load');
        wp_dequeue_script('lazy-load-js');
        wp_dequeue_script('wp-lazy-loading');

        // Dequeue this plugin's own lazy-load script (native loading="lazy" is sufficient)
        wp_dequeue_script('image-optimizer-lazy');
        wp_dequeue_script('image-optimizer-lazy-js');

        // Remove jQuery migrate (for WordPress backward compat, not needed for modern sites)
        wp_dequeue_script('jquery-migrate');
    }
}
