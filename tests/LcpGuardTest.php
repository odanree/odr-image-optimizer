<?php

declare(strict_types=1);

use ImageOptimizer\Services\PriorityService;
use ImageOptimizer\Services\CleanupService;

test('priority service injects high-priority preload tags', function () {
    // 1. Arrange: Mock the settings to ensure preloading is enabled
    update_option('odr_image_optimizer_settings', [
        'preload_fonts' => '1',
    ]);

    $service = new PriorityService();

    // 2. Act: Trigger the detection and injection
    // First, we need to set up a post with a featured image
    $post_id = wp_insert_post([
        'post_type'    => 'post',
        'post_status'  => 'publish',
        'post_title'   => 'Test Post',
        'post_content' => 'Test content',
    ]);

    set_post_thumbnail($post_id, 0); // Set featured image (normally an attachment ID)

    // Detect LCP candidate
    $service->detect_lcp_id();

    // Buffer the preload injection
    ob_start();
    $service->inject_preload();
    $output = ob_get_clean();

    // 3. Assert: Verify the LCP-optimizing tags are present
    expect($output)->toContain('rel="preload"')
        ->toContain('as="image"')
        ->toContain('fetchpriority="high"');

    // Cleanup
    wp_delete_post($post_id, true);
});

test('cleanup service removes emoji bloat when enabled', function () {
    // 1. Arrange: Ensure the setting is active
    update_option('odr_image_optimizer_settings', [
        'kill_bloat' => '1',
    ]);

    // Mock that we're on the frontend (not admin)
    $this->assertTrue(! is_admin(), 'Should be on frontend for this test');

    // 2. Act: Trigger cleanup
    $cleanup = new CleanupService();
    $cleanup->remove_bloat();

    // 3. Assert: Verify emoji actions are removed
    // When the setting is enabled, remove_bloat() removes the emoji actions
    $has_emoji_action = has_action('wp_head', 'print_emoji_detection_script');
    expect($has_emoji_action)->toBeFalse();
});

test('cleanup service dequeues lazy-load script', function () {
    // 1. Arrange: Enqueue the lazy-load script
    wp_enqueue_script('wp-lazy-load', '//example.com/lazy-load.js', [], null);
    expect(wp_script_is('wp-lazy-load', 'enqueued'))->toBeTrue();

    // 2. Act: Trigger cleanup
    $cleanup = new CleanupService();
    $cleanup->remove_bloat();

    // 3. Assert: Verify the script is dequeued
    expect(wp_script_is('wp-lazy-load', 'enqueued'))->toBeFalse();
});

test('priority service respects preload setting when disabled', function () {
    // 1. Arrange: Disable preloading
    update_option('odr_image_optimizer_settings', [
        'preload_fonts' => '0',
    ]);

    $service = new PriorityService();

    // 2. Act: Buffer the preload injection
    ob_start();
    $service->inject_preload();
    $output = ob_get_clean();

    // 3. Assert: Verify preload tags are NOT emitted
    expect($output)->not->toContain('rel="preload"');
});

test('cleanup service respects kill_bloat setting when disabled', function () {
    // 1. Arrange: Disable bloat removal
    update_option('odr_image_optimizer_settings', [
        'kill_bloat' => '0',
    ]);

    // Add the emoji action back for testing
    add_action('wp_head', 'print_emoji_detection_script', 7);

    // 2. Act: Trigger cleanup
    $cleanup = new CleanupService();
    $cleanup->remove_bloat();

    // 3. Assert: Verify emoji action is still present (NOT removed)
    $has_emoji_action = has_action('wp_head', 'print_emoji_detection_script');
    expect($has_emoji_action)->not->toBeFalse();

    // Cleanup
    remove_action('wp_head', 'print_emoji_detection_script', 7);
});
