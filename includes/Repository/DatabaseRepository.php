<?php

declare(strict_types=1);
if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Database Repository - Decoupled from WordPress global functions
 *
 * @package ImageOptimizer\Repository
 */

namespace ImageOptimizer\Repository;

use ImageOptimizer\Exception\OptimizationFailedException;

readonly class DatabaseRepository
{
    public function __construct(
        private \wpdb $wpdb,
    ) {}

    /**
     * Create required database tables
     *
     * @return void
     *
     * @throws OptimizationFailedException
     */
    public function createTables(): void
    {
        $charsetCollate = $this->wpdb->get_charset_collate();

        $historyTable = $this->wpdb->prefix . 'image_optimizer_history';
        $historySql = "CREATE TABLE IF NOT EXISTS {$historyTable} (
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
        ) {$charsetCollate};";

        $cacheTable = $this->wpdb->prefix . 'image_optimizer_cache';
        $cacheSql = "CREATE TABLE IF NOT EXISTS {$cacheTable} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            cache_key VARCHAR(255) NOT NULL,
            cache_value LONGTEXT,
            expires_at DATETIME,
            PRIMARY KEY  (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expires_at (expires_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        try {
            dbDelta($historySql);
            dbDelta($cacheSql);
        } catch (\Throwable $e) {
            throw new OptimizationFailedException("Failed to create database tables: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Save an optimization result
     *
     * @param int $attachmentId
     * @param int $originalSize
     * @param int $optimizedSize
     * @param float $compressionRatio
     * @param string $method
     * @param bool $webpAvailable
     * @param string $status
     * @param string|null $errorMessage
     * @return int|false
     *
     * @throws OptimizationFailedException
     */
    public function saveOptimizationResult(
        int $attachmentId,
        int $originalSize,
        int $optimizedSize,
        float $compressionRatio,
        string $method,
        bool $webpAvailable,
        string $status,
        ?string $errorMessage = null,
    ): int|false {
        $table = $this->wpdb->prefix . 'image_optimizer_history';

        return $this->wpdb->insert(
            $table,
            [
                'attachment_id'       => $attachmentId,
                'original_size'       => $originalSize,
                'optimized_size'      => $optimizedSize,
                'compression_ratio'   => $compressionRatio,
                'optimization_method' => $method,
                'webp_available'      => $webpAvailable ? 1 : 0,
                'status'              => $status,
                'error_message'       => $errorMessage,
            ],
            ['%d', '%d', '%d', '%f', '%s', '%d', '%s', '%s'],
        );
    }

    /**
     * Get the latest optimization result for an attachment
     *
     * @param int $attachmentId
     * @return array|null
     */
    public function getOptimizationResult(int $attachmentId): ?array
    {
        $table = $this->wpdb->prefix . 'image_optimizer_history';

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE attachment_id = %d ORDER BY optimized_at DESC LIMIT 1",
                $attachmentId,
            ),
            ARRAY_A,
        );

        return $result ? (array) $result : null;
    }

    /**
     * Get optimization statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $table = $this->wpdb->prefix . 'image_optimizer_history';

        $result = $this->wpdb->get_row(
            "SELECT 
                COUNT(*) as total_optimized,
                SUM(original_size) as total_original_size,
                SUM(optimized_size) as total_optimized_size,
                AVG(compression_ratio) as average_compression,
                SUM(webp_available) as webp_count
            FROM {$table} 
            WHERE status = 'completed'",
        );

        return (array) ($result ?? []);
    }

    /**
     * Cache a value
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expiresIn Seconds until expiration, or null for no expiration
     * @return bool
     */
    public function cacheSet(string $key, mixed $value, ?int $expiresIn = null): bool
    {
        $table = $this->wpdb->prefix . 'image_optimizer_cache';
        $expiresAt = $expiresIn ? date('Y-m-d H:i:s', time() + $expiresIn) : null;

        $result = $this->wpdb->replace(
            $table,
            [
                'cache_key'   => $key,
                'cache_value' => maybe_serialize($value),
                'expires_at'  => $expiresAt,
            ],
            ['%s', '%s', '%s'],
        );

        return $result !== false;
    }

    /**
     * Retrieve a cached value
     *
     * @param string $key
     * @return mixed|null
     */
    public function cacheGet(string $key): mixed
    {
        $table = $this->wpdb->prefix . 'image_optimizer_cache';

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT cache_value FROM {$table} WHERE cache_key = %s AND (expires_at IS NULL OR expires_at > NOW())",
                $key,
            ),
        );

        return $result ? maybe_unserialize($result->cache_value) : null;
    }
}
