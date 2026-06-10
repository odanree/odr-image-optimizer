<?php

declare(strict_types=1);

/**
 * Uninstall handler for ODR Image Optimizer.
 *
 * Removes plugin options and the backup directory created under the uploads
 * folder. Runs only when the plugin is deleted from the WordPress admin.
 *
 * @package ImageOptimizer
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('odr_image_optimizer_settings');
delete_option('image_optimizer_settings');
delete_option('odr_image_optimizer_settings_v1_migrated');

$uploads = wp_upload_dir(null, false);
if (! is_array($uploads) || ! empty($uploads['error']) || empty($uploads['basedir'])) {
    return;
}

$backup_root = rtrim((string) $uploads['basedir'], '/\\') . '/odr-image-optimizer';
if (! is_dir($backup_root)) {
    return;
}

if (! function_exists('WP_Filesystem')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

global $wp_filesystem;
if (! $wp_filesystem) {
    WP_Filesystem();
}

if ($wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->is_dir($backup_root)) {
    $wp_filesystem->delete($backup_root, true);
}
