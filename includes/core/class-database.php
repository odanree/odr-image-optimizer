<?php

declare(strict_types=1);

/**
 * Database management class
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}
/**
 * Database class for managing plugin tables
 */
class Database
{
    /**
     * Create database tables on plugin activation
     */
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Image optimization history table
        $table_name = $wpdb->prefix . 'image_optimizer_history';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			attachment_id BIGINT UNSIGNED NOT NULL,
			original_size BIGINT UNSIGNED NOT NULL,
			optimized_size BIGINT UNSIGNED NOT NULL,
			compression_ratio FLOAT NOT NULL,
			optimization_method VARCHAR(50) NOT NULL,
			webp_available BOOLEAN DEFAULT false,
			status VARCHAR(20) DEFAULT 'pending',
			error_message LONGTEXT,
			optimized_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY attachment_id (attachment_id),
			KEY status (status)
		) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Image optimization cache table
        $cache_table = $wpdb->prefix . 'image_optimizer_cache';

        $cache_sql = "CREATE TABLE IF NOT EXISTS $cache_table (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			cache_key VARCHAR(255) NOT NULL,
			cache_value LONGTEXT,
			expires_at DATETIME,
			PRIMARY KEY  (id),
			UNIQUE KEY cache_key (cache_key),
			KEY expires_at (expires_at)
		) $charset_collate;";

        dbDelta($cache_sql);
    }

    /**
     * Get optimization history for an image
     *
     * @param int $attachment_id The attachment ID.
     * @return object|null
     */
    public static function get_optimization_history($attachment_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'image_optimizer_history';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE attachment_id = %d ORDER BY optimized_at DESC LIMIT 1",
                $attachment_id,
            ),
        );
    }

    /**
     * Save optimization result
     *
     * @param int   $attachment_id The attachment ID.
     * @param array $data The optimization data.
     * @return int|false
     */
    public static function save_optimization_result($attachment_id, $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'image_optimizer_history';

        return $wpdb->insert(
            $table,
            [
                'attachment_id'       => $attachment_id,
                'original_size'       => $data['original_size'],
                'optimized_size'      => $data['optimized_size'],
                'compression_ratio'   => $data['compression_ratio'],
                'optimization_method' => $data['method'],
                'webp_available'      => $data['webp_available'],
                'status'              => $data['status'],
                'error_message'       => isset($data['error']) ? $data['error'] : null,
            ],
            [ '%d', '%d', '%d', '%f', '%s', '%d', '%s', '%s' ],
        );
    }

    /**
     * Get cache value
     *
     * @param string $key The cache key.
     * @return mixed|null
     */
    public static function get_cache($key)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'image_optimizer_cache';

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT cache_value FROM $table WHERE cache_key = %s AND (expires_at IS NULL OR expires_at > NOW())",
                $key,
            ),
        );

        return $result ? maybe_unserialize($result->cache_value) : null;
    }

    /**
     * Set cache value
     *
     * @param string $key The cache key.
     * @param mixed  $value The cache value.
     * @param int    $expires_in Expiration in seconds.
     * @return int|false
     */
    public static function set_cache($key, $value, $expires_in = null)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'image_optimizer_cache';

        $expires_at = $expires_in ? date('Y-m-d H:i:s', time() + $expires_in) : null;

        return $wpdb->replace(
            $table,
            [
                'cache_key'   => $key,
                'cache_value' => maybe_serialize($value),
                'expires_at'  => $expires_at,
            ],
            [ '%s', '%s', '%s' ],
        );
    }

    /**
     * Get optimization statistics
     *
     * @return object
     */
    public static function get_statistics()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'image_optimizer_history';

        return $wpdb->get_row(
            "SELECT 
				COUNT(*) as total_optimized,
				SUM(original_size) as total_original_size,
				SUM(optimized_size) as total_optimized_size,
				AVG(compression_ratio) as average_compression,
				SUM(webp_available) as webp_count
			FROM $table 
			WHERE status = 'completed'",
        );
    }
}
