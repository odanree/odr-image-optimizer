<?php

declare(strict_types=1);

use ImageOptimizer\Services\PriorityService;
use ImageOptimizer\Services\CleanupService;
use PHPUnit\Framework\TestCase;

class LcpGuardTest extends TestCase
{
    /**
     * Priority service injects high-priority preload tags
     *
     * Tests that the PriorityService correctly injects preload tags
     * with fetchpriority="high" to force LCP optimization.
     *
     * @return void
     */
    public function test_priority_service_injects_high_priority_preload_tags(): void
    {
        // Arrange: Enable preloading setting
        update_option('odr_image_optimizer_settings', [
            'preload_fonts' => '1',
        ]);

        $service = new PriorityService();

        // Act: Detect LCP and buffer preload output
        $service->detect_lcp_id();

        ob_start();
        $service->inject_preload();
        $output = ob_get_clean();

        // Assert: Verify preload tags are present
        $this->assertStringContainsString('rel="preload"', $output);
        $this->assertStringContainsString('as="image"', $output);
        $this->assertStringContainsString('fetchpriority="high"', $output);
    }

    /**
     * Cleanup service removes emoji bloat when enabled
     *
     * Tests that the CleanupService correctly removes emoji detection
     * script when the kill_bloat setting is enabled.
     *
     * @return void
     */
    public function test_cleanup_service_removes_emoji_bloat_when_enabled(): void
    {
        // Arrange: Enable bloat removal setting
        update_option('odr_image_optimizer_settings', [
            'kill_bloat' => '1',
        ]);

        // Act: Trigger cleanup
        $cleanup = new CleanupService();
        $cleanup->remove_bloat();

        // Assert: Verify emoji actions are removed
        $has_emoji_action = has_action('wp_head', 'print_emoji_detection_script');
        $this->assertFalse($has_emoji_action, 'Emoji detection script should be removed');
    }

    /**
     * Cleanup service dequeues lazy-load script
     *
     * Tests that the CleanupService correctly dequeues the WordPress
     * lazy-load script, which is redundant with native loading="lazy".
     *
     * @return void
     */
    public function test_cleanup_service_dequeues_lazy_load_script(): void
    {
        // Arrange: Enqueue the lazy-load script
        wp_enqueue_script('wp-lazy-load', '//example.com/lazy-load.js', [], null);
        $this->assertTrue(wp_script_is('wp-lazy-load', 'enqueued'), 'Script should be enqueued');

        // Act: Trigger cleanup
        $cleanup = new CleanupService();
        $cleanup->remove_bloat();

        // Assert: Verify script is dequeued
        $this->assertFalse(
            wp_script_is('wp-lazy-load', 'enqueued'),
            'wp-lazy-load should be dequeued'
        );
    }

    /**
     * Priority service respects preload setting when disabled
     *
     * Tests that the PriorityService respects user settings
     * and does NOT emit preload tags when disabled.
     *
     * @return void
     */
    public function test_priority_service_respects_preload_setting_when_disabled(): void
    {
        // Arrange: Disable preloading
        update_option('odr_image_optimizer_settings', [
            'preload_fonts' => '0',
        ]);

        $service = new PriorityService();

        // Act: Buffer preload output
        ob_start();
        $service->inject_preload();
        $output = ob_get_clean();

        // Assert: Verify preload tags are NOT emitted
        $this->assertStringNotContainsString(
            'rel="preload"',
            $output,
            'Preload tags should not be emitted when disabled'
        );
    }

    /**
     * Cleanup service respects kill_bloat setting when disabled
     *
     * Tests that the CleanupService respects user settings
     * and does NOT remove emoji scripts when disabled.
     *
     * @return void
     */
    public function test_cleanup_service_respects_kill_bloat_setting_when_disabled(): void
    {
        // Arrange: Disable bloat removal
        update_option('odr_image_optimizer_settings', [
            'kill_bloat' => '0',
        ]);

        // Add the emoji action back for testing
        add_action('wp_head', 'print_emoji_detection_script', 7);

        // Act: Trigger cleanup
        $cleanup = new CleanupService();
        $cleanup->remove_bloat();

        // Assert: Verify emoji action is still present (NOT removed)
        $has_emoji_action = has_action('wp_head', 'print_emoji_detection_script');
        $this->assertNotFalse(
            $has_emoji_action,
            'Emoji detection script should NOT be removed when setting is disabled'
        );

        // Cleanup
        remove_action('wp_head', 'print_emoji_detection_script', 7);
    }
}
