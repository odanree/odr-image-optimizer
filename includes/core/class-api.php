<?php

declare(strict_types=1);



if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * REST API endpoints for Image Optimizer
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

/**
 * REST API class
 */
class API
{
    public const NAMESPACE = 'image-optimizer/v1';
    public const NONCE_FIELD = 'image_optimizer_nonce';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Routes are registered directly from the plugin file
    }

    /**
     * Register REST routes
     */
    public function register_routes()
    {
        // Test endpoint (public)
        register_rest_route(
            self::NAMESPACE,
            '/test',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'test_endpoint' ],
                'permission_callback' => '__return_true',
            ],
        );

        // Get optimization statistics - requires admin permission
        register_rest_route(
            self::NAMESPACE,
            '/stats',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_statistics' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
            ],
        );

        // Get image optimization history - requires admin permission
        register_rest_route(
            self::NAMESPACE,
            '/history/(?P<attachment_id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_history' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
                'args'                => [
                    'attachment_id' => [
                        'type'     => 'integer',
                        'required' => true,
                    ],
                ],
            ],
        );

        // Bulk get images - requires admin permission
        register_rest_route(
            self::NAMESPACE,
            '/images',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_images' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
                'args'                => [
                    'paged'  => [
                        'type'    => 'integer',
                        'default' => 1,
                    ],
                    'status' => [
                        'type' => 'string',
                    ],
                ],
            ],
        );

        // Optimize single image - requires admin permission
        register_rest_route(
            self::NAMESPACE,
            '/optimize/(?P<attachment_id>\d+)',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'optimize_image' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
                'args'                => [
                    'attachment_id' => [
                        'type'     => 'integer',
                        'required' => true,
                    ],
                ],
            ],
        );

        // Revert optimization - requires admin permission
        register_rest_route(
            self::NAMESPACE,
            '/revert/(?P<attachment_id>\d+)',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'revert_image' ],
                'permission_callback' => [ $this, 'check_admin_permission' ],
                'args'                => [
                    'attachment_id' => [
                        'type'     => 'integer',
                        'required' => true,
                    ],
                ],
            ],
        );
    }

    /**
     * Check admin permission
     *
     * @return bool
     */
    public function check_admin_permission()
    {
        return current_user_can('manage_options');
    }

    /**
     * Get statistics
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_statistics($request)
    {
        $stats = Database::get_statistics();

        $response = rest_ensure_response(
            [
                'total_optimized'     => (int) $stats->total_optimized,
                'total_original_size' => (int) $stats->total_original_size,
                'total_optimized_size' => (int) $stats->total_optimized_size,
                'total_savings'       => (int) ($stats->total_original_size - $stats->total_optimized_size),
                'average_compression' => (float) $stats->average_compression,
                'webp_count'          => (int) $stats->webp_count,
            ],
        );

        // Cache for 1 hour (3600 seconds)
        $response->set_headers(
            [
                'Cache-Control' => 'public, max-age=3600',
            ],
        );

        return $response;
    }

    /**
     * Test endpoint
     *
     * @return \WP_REST_Response
     */
    public function test_endpoint()
    {
        return rest_ensure_response(
            [
                'status' => 'API is working!',
                'timestamp' => current_time('mysql'),
            ],
        );
    }

    /**
     * Get optimization history for an image
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_history($request)
    {
        $attachment_id = $request['attachment_id'];
        $history = Database::get_optimization_history($attachment_id);

        if (! $history) {
            return rest_ensure_response(
                [
                    'error' => 'No optimization history found',
                ],
                404,
            );
        }

        return rest_ensure_response($this->format_history($history));
    }

    /**
     * Get paginated list of images
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_images($request)
    {
        $paged = $request->get_param('paged') ?? 1;
        $per_page = 20;

        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ],
            'post_status'    => 'inherit',
            'paged'          => $paged,
            'posts_per_page' => $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new \WP_Query($args);
        $images = [];

        foreach ($query->posts as $post) {
            $attached_file = get_attached_file($post->ID);
            $file_size = $attached_file ? filesize($attached_file) : 0;
            $history = Database::get_optimization_history($post->ID);

            // Only consider image as optimized if history exists and status is not 'reverted'
            $is_optimized = ! empty($history) && (! isset($history->status) || $history->status !== 'reverted');

            $images[] = [
                'id'              => $post->ID,
                'title'           => $post->post_title,
                'filename'        => basename($attached_file ? $attached_file : ''),
                'url'             => wp_get_attachment_url($post->ID),
                'size'            => $file_size,
                'optimized'       => $is_optimized,
                'optimization'    => $history ? $this->format_history($history) : null,
            ];
        }

        $response = rest_ensure_response(
            [
                'images' => $images,
                'paged'  => $paged,
                'total'  => $query->found_posts,
                'pages'  => $query->max_num_pages,
            ],
        );

        // Cache for 30 minutes (1800 seconds) for image lists
        $response->set_headers(
            [
                'Cache-Control' => 'public, max-age=1800',
            ],
        );

        return $response;
    }

    /**
     * Optimize a single image
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function optimize_image($request)
    {
        $attachment_id = $request['attachment_id'];

        if (! get_post($attachment_id)) {
            return new \WP_Error('invalid_attachment', 'Invalid attachment ID', [ 'status' => 404 ]);
        }

        $optimizer = new Optimizer();
        $result = $optimizer->optimize_attachment($attachment_id);

        if ($result['success']) {
            return rest_ensure_response($result);
        }

        return new \WP_Error('optimization_failed', $result['error'], [ 'status' => 400 ]);
    }

    /**
     * Revert image optimization
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function revert_image($request)
    {
        $attachment_id = $request['attachment_id'];

        if (! get_post($attachment_id)) {
            return new \WP_Error('invalid_attachment', 'Invalid attachment ID', [ 'status' => 404 ]);
        }

        $optimizer = new Optimizer();
        $result = $optimizer->revert_optimization($attachment_id);

        if ($result['success']) {
            return rest_ensure_response($result);
        }

        return new \WP_Error('revert_failed', $result['error'], [ 'status' => 400 ]);
    }

    /**
     * Format history for API response
     *
     * @param object $history The history object.
     * @return array
     */
    private function format_history($history)
    {
        return [
            'id'                => (int) $history->id,
            'attachment_id'     => (int) $history->attachment_id,
            'original_size'     => (int) $history->original_size,
            'optimized_size'    => (int) $history->optimized_size,
            'savings'           => (int) ($history->original_size - $history->optimized_size),
            'compression_ratio' => (float) $history->compression_ratio,
            'method'            => $history->optimization_method,
            'webp_available'    => (bool) $history->webp_available,
            'status'            => $history->status,
            'optimized_at'      => $history->optimized_at,
        ];
    }
}
