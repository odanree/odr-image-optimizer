<?php

declare(strict_types=1);


/**
 * Image optimization engine
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Core;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}
/**
 * Main image optimizer class
 */
class Optimizer implements OptimizerInterface
{
    /**
     * Compression levels
     */
    public const COMPRESSION_LOW = 1;
    public const COMPRESSION_MEDIUM = 2;
    public const COMPRESSION_HIGH = 3;

    /**
     * Tool registry for external tools (dependency injection)
     *
     * @var ToolRegistry
     */
    private $tool_registry;

    /**
     * Optimizer configuration (dependency injection)
     *
     * @var OptimizerConfig
     */
    private $config;

    /**
     * Constructor
     *
     * @param ToolRegistry|null      $tool_registry Tool registry for dependency injection.
     *                                              If null, creates a new auto-detected registry.
     * @param OptimizerConfig|null   $config Optimizer configuration for dependency injection.
     *                                        If null, loads from WordPress options.
     */
    public function __construct(?ToolRegistry $tool_registry = null, ?OptimizerConfig $config = null)
    {
        $this->tool_registry = $tool_registry ?? new ToolRegistry();
        $this->config = $config ?? OptimizerConfig::from_wordpress_options();
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        // Optimize image on upload
        add_filter('wp_handle_upload', [ $this, 'optimize_on_upload' ]);

        // Optimize existing images via admin
        add_action('admin_action_image_optimizer_optimize', [ $this, 'optimize_single_image' ]);

        // Bulk optimization via AJAX
        add_action('wp_ajax_image_optimizer_bulk_optimize', [ $this, 'ajax_bulk_optimize' ]);
    }

    /**
     * Get the tool registry
     *
     * Allows access to registered external tools for optimization.
     *
     * @return ToolRegistry The tool registry instance.
     */
    public function get_tool_registry(): ToolRegistry
    {
        return $this->tool_registry;
    }

    /**
     * Get the configuration
     *
     * @return OptimizerConfig The configuration instance.
     */
    public function get_config(): OptimizerConfig
    {
        return $this->config;
    }

    /**
     * Optimize image on upload
     *
     * @param array $upload The upload data.
     * @return array
     */
    public function optimize_on_upload($upload)
    {
        $config = $this->config;

        if (! $config->should_auto_optimize()) {
            return $upload;
        }

        if (isset($upload['file']) && $this->is_optimizable($upload['file'])) {
            $this->optimize_file($upload['file']);
        }

        return $upload;
    }

    /**
     * Optimize a single image
     */
    public function optimize_single_image()
    {
        check_admin_referer('image_optimizer_nonce');

        if (! current_user_can('manage_options')) {
            wp_die('Unauthorized', 403);
        }

        $attachment_id = isset($_GET['attachment_id']) ? absint($_GET['attachment_id']) : 0;

        if (! $attachment_id) {
            wp_die('Invalid attachment ID', 400);
        }

        $result = $this->optimize_attachment($attachment_id);

        wp_send_json($result);
    }

    /**
     * AJAX bulk optimization
     */
    public function ajax_bulk_optimize()
    {
        check_ajax_referer('image_optimizer_nonce');

        if (! current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $attachment_ids = isset($_POST['attachment_ids']) ? array_map('absint', $_POST['attachment_ids']) : [];

        if (empty($attachment_ids)) {
            wp_send_json_error('No attachments provided');
        }

        $results = [];
        foreach ($attachment_ids as $attachment_id) {
            $results[ $attachment_id ] = $this->optimize_attachment($attachment_id);
        }

        wp_send_json_success($results);
    }

    /**
     * Optimize an attachment
     *
     * @param int $attachment_id The attachment ID.
     * @return array
     */
    /**
     * Get available memory in bytes
     *
     * @return int Memory available in bytes
     */
    private function get_memory_available()
    {
        $memory_limit = WP_MEMORY_LIMIT;
        
        // Convert format like "256M" to bytes
        if (function_exists('wp_convert_hr_to_bytes')) {
            $limit_bytes = wp_convert_hr_to_bytes($memory_limit);
        } else {
            // Fallback conversion
            $memory_limit = (string) $memory_limit;
            $value = (int) $memory_limit;
            $unit = strtoupper(substr($memory_limit, -1));
            
            switch ($unit) {
                case 'K':
                    $value *= 1024;
                    break;
                case 'M':
                    $value *= 1024 * 1024;
                    break;
                case 'G':
                    $value *= 1024 * 1024 * 1024;
                    break;
            }
            $limit_bytes = $value;
        }
        
        $memory_used = memory_get_usage(true);
        return max(0, $limit_bytes - $memory_used);
    }

    public function optimize_attachment($attachment_id): Result
    {
        try {
            // Check memory before attempting optimization
            $memory_available = $this->get_memory_available();
            
            // Need at least 100MB free for safe optimization
            if ($memory_available < 100 * 1024 * 1024) {
                return Result::failure('Insufficient memory. Please increase WP_MEMORY_LIMIT');
            }

            $file = get_attached_file($attachment_id);

            if (! $file || ! file_exists($file)) {
                return Result::failure('File not found');
            }

            if (! $this->is_optimizable($file)) {
                return Result::failure('File type not supported');
            }

            $original_size = filesize($file);
            
            // Get image dimensions
            $image_info = @getimagesize($file);
            $width = $image_info[0] ?? 0;
            $height = $image_info[1] ?? 0;
            
            // Create image context for hooks
            $context = new ImageContext(
                $file,
                (int) $attachment_id,
                [
                    'width' => $width,
                    'height' => $height,
                    'original_size' => $original_size,
                    'mime_type' => $image_info['mime'] ?? mime_content_type($file),
                    'compression_level' => 'medium', // Default
                ]
            );
            
            /**
             * Fires before image optimization starts
             *
             * Allows plugins to inspect image metadata without re-reading the file.
             * Hook functions can access: $context->width, $context->height, $context->original_size
             *
             * @param ImageContext $context Image context with metadata.
             */
            do_action('image_optimizer_before_optimize', $context);

            // Create backup before optimization
            $backup_file = $this->create_backup($file, $attachment_id);

            // Get optimization method based on file type
            $method = $this->get_optimization_method($file);

            // Perform optimization
            $result = $this->optimize_file($file, $method);

            if (! $result) {
                return Result::failure('Optimization failed');
            }

            $optimized_size = filesize($file);
            $savings = $original_size - $optimized_size;
            $compression_ratio = $savings > 0 ? ($savings / $original_size) * 100 : 0;
            
            // Update context with optimization results
            $context->set('optimized_size', $optimized_size);
            $context->set('savings', $savings);
            $context->set('compression_ratio', $compression_ratio);

            // Check if WebP conversion is enabled and can be created
            $config = $this->config;
            $webp_available = false;
            if ($config->should_create_webp()) {
                $webp_available = $this->can_create_webp($file);
                if ($webp_available) {
                    $this->create_webp_version($file);
                    $context->set('webp_created', true);
                }
            }

            // Save optimization result
            Database::save_optimization_result(
                $attachment_id,
                [
                    'original_size'  => $original_size,
                    'optimized_size' => $optimized_size,
                    'compression_ratio' => $compression_ratio,
                    'method'         => $method,
                    'webp_available' => $webp_available,
                    'status'         => 'completed',
                    'backup_file'    => $backup_file,
                ],
            );
            
            /**
             * Fires after successful image optimization
             *
             * Allows plugins to perform post-processing without re-reading files.
             * Hook functions can access all image metadata via ImageContext object.
             *
             * @param ImageContext $context Image context with metadata and optimization results.
             */
            do_action('image_optimizer_after_optimize', $context);

            // Optimize all attachment subsizes (thumbnail, medium, large, etc.)
            // This is critical for Lighthouse responsive image compliance
            $this->optimize_attachment_subsizes((int) $attachment_id);

            return Result::success(
                [
                    'original_size'      => $original_size,
                    'optimized_size'     => $optimized_size,
                    'savings'            => $savings,
                    'compression_ratio'  => $compression_ratio,
                    'webp_available'     => $webp_available,
                ],
                sprintf(
                    'Image optimized: %.1f%% compression',
                    $compression_ratio
                )
            );
        } catch (\Exception $e) {
            Database::save_optimization_result(
                $attachment_id,
                [
                    'original_size'  => 0,
                    'optimized_size' => 0,
                    'compression_ratio' => 0,
                    'method'         => 'error',
                    'webp_available' => false,
                    'status'         => 'failed',
                    'error'          => $e->getMessage(),
                ],
            );

            return Result::from_exception($e);
        }
    }

    /**
     * Optimize a file
     *
     * @param string $file_path The file path.
     * @param string $method The optimization method.
     * @return bool
     */
    protected function optimize_file($file_path, $method = 'standard')
    {
        if (! file_exists($file_path)) {
            return false;
        }

        $file_type = wp_check_filetype($file_path);
        $mime_type = $file_type['type'];

        switch ($mime_type) {
            case 'image/jpeg':
                return $this->optimize_jpeg($file_path);
            case 'image/png':
                return $this->optimize_png($file_path);
            case 'image/webp':
                return $this->optimize_webp($file_path);
            case 'image/gif':
                return $this->optimize_gif($file_path);
            default:
                return false;
        }
    }

    /**
     * Optimize JPEG image
     *
     * @param string $file_path The file path.
     * @return bool
     */
    protected function optimize_jpeg($file_path)
    {
        // Core Web Vitals: Aggressive compression improves LCP (Largest Contentful Paint)
        // https://developer.chrome.com/docs/performance/insights/image-delivery
        $config = $this->config;
        $compression = 'medium';  // Default compression level

        try {
            // Try ImageMagick first for better compression (50%+ reduction)
            if (extension_loaded('imagick')) {
                return $this->optimize_jpeg_imagick($file_path, $compression);
            }

            // Fall back to GD Library
            return $this->optimize_jpeg_gd($file_path, $compression);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize JPEG using ImageMagick (best compression)
     *
     * @param string $file_path The file path.
     * @param string $compression Compression level.
     * @return bool
     */
    protected function optimize_jpeg_imagick($file_path, $compression)
    {
        try {
            $im = new \Imagick($file_path);

            // Strip metadata for additional 5-10% reduction
            $im->stripImage();

            // For JPEGs: Skip color quantization (causes color banding on photos)
            // Instead rely on quality settings and metadata stripping for compression
            // Color quantization is better for indexed-color images (PNG, GIF)

            // Set compression quality based on level
            // Quality 48-55: 35-45% compression with acceptable color quality
            $quality = $compression === 'high' ? 48 : ($compression === 'low' ? 55 : 52);
            $im->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $im->setImageCompressionQuality($quality);

            // Progressive JPEG for better perceived performance
            $im->setInterlaceScheme(\Imagick::INTERLACE_JPEG);

            $im->writeImage($file_path);
            $im->destroy();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize JPEG using GD Library (fallback)
     *
     * @param string $file_path The file path.
     * @param string $compression Compression level.
     * @return bool
     */
    protected function optimize_jpeg_gd($file_path, $compression)
    {
        // Quality 48-55: 35-45% compression with acceptable color quality
        $quality = $compression === 'high' ? 48 : ($compression === 'low' ? 55 : 52);

        $image = imagecreatefromjpeg($file_path);
        if (! $image) {
            return false;
        }

        // Apply progressive encoding for better compression and perceived performance
        imageinterlace($image, true);
        $result = imagejpeg($image, $file_path, $quality);
        imagedestroy($image);

        return $result;
    }

    /**
     * Optimize PNG image (following Chrome Core Web Vitals best practices)
     * https://developer.chrome.com/docs/performance/insights/image-delivery
     *
     * @param string $file_path The file path.
     * @return bool
     */
    protected function optimize_png($file_path)
    {
        // Core Web Vitals: PNG compression level 9 = maximum compression for best LCP
        $config = $this->config;
        $compression = 'medium';  // Default compression level

        try {
            // Try ImageMagick first for better compression
            if (extension_loaded('imagick')) {
                return $this->optimize_png_imagick($file_path, $compression);
            }

            // Fall back to GD Library
            return $this->optimize_png_gd($file_path, $compression);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize PNG using ImageMagick (best compression)
     *
     * @param string $file_path The file path.
     * @param string $compression Compression level.
     * @return bool
     */
    protected function optimize_png_imagick($file_path, $compression)
    {
        try {
            $im = new \Imagick($file_path);

            // Strip metadata
            $im->stripImage();

            // Color reduction for PNG (up to 40% additional reduction)
            $colors = $compression === 'high' ? 128 : ($compression === 'low' ? 256 : 256);
            $im->quantizeImage($colors, \Imagick::COLORSPACE_RGB, 0, false, false);

            // Compression quality
            $quality = $compression === 'high' ? 85 : 95;
            $im->setImageCompressionQuality($quality);

            // PNG interlacing
            $im->setInterlaceScheme(\Imagick::INTERLACE_PNG);

            $im->writeImage($file_path);
            $im->destroy();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize PNG using GD Library (fallback)
     *
     * @param string $file_path The file path.
     * @param string $compression Compression level.
     * @return bool
     */
    protected function optimize_png_gd($file_path, $compression)
    {
        $compression_level = $this->get_compression_level($compression);

        $image = imagecreatefrompng($file_path);
        if (! $image) {
            return false;
        }

        // Apply interlacing for progressive display and better perceived performance
        imageinterlace($image, true);
        $result = imagepng($image, $file_path, $compression_level);
        imagedestroy($image);

        return $result;
    }

    /**
     * Optimize WebP image
     *
     * @param string $file_path The file path.
     * @return bool
     */
    protected function optimize_webp($file_path)
    {
        try {
            $image = imagecreatefromwebp($file_path);
            if (! $image) {
                return false;
            }

            $result = imagewebp($image, $file_path, 48);
            imagedestroy($image);

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize GIF image
     *
     * @param string $file_path The file path.
     * @return bool
     */
    protected function optimize_gif($file_path)
    {
        // GIF optimization is complex; for now just return true
        // In production, use external services
        return true;
    }

    /**
     * Create WebP version of the image (modern format per Core Web Vitals)
     * https://developer.chrome.com/docs/performance/insights/image-delivery
     *
     * @param string $file_path The original file path.
     * @return bool|string The WebP file path or false.
     */
    private function create_webp_version($file_path)
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        // Check available memory before WebP conversion (expensive operation)
        $memory_available = $this->get_memory_available();
        
        // Skip WebP if less than 100MB available (safe margin)
        if ($memory_available < 100 * 1024 * 1024) {
            return false;  // Memory too tight for WebP conversion
        }

        $file_info = pathinfo($file_path);
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

        try {
            $file_type = wp_check_filetype($file_path);
            $mime_type = $file_type['type'];

            $image = null;
            switch ($mime_type) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($file_path);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($file_path);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($file_path);
                    break;
            }

            if (! $image || ! function_exists('imagewebp')) {
                return false;
            }

            // WebP quality: 48 (same as JPEG for consistency)
            $result = imagewebp($image, $webp_path, 48);
            imagedestroy($image);

            return $result ? $webp_path : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a file can be optimized
     *
     * @param string $file_path The file path.
     * @return bool
     */
    private function is_optimizable($file_path)
    {
        $file_type = wp_check_filetype($file_path);
        $mime_type = $file_type['type'];

        $optimizable_types = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        return in_array($mime_type, $optimizable_types, true);
    }

    /**
     * Get optimization method for a file
     *
     * @param string $file_path The file path.
     * @return string
     */
    private function get_optimization_method($file_path)
    {
        // Check available external APIs or tools
        // Priority: external API > GD library > system tools
        return 'gd_library';
    }

    /**
     * Check if WebP can be created
     *
     * @param string $file_path The file path.
     * @return bool
     */
    private function can_create_webp($file_path)
    {
        $file_type = wp_check_filetype($file_path);
        $mime_type = $file_type['type'];

        // Only create WebP for JPEG and PNG
        if (! in_array($mime_type, [ 'image/jpeg', 'image/png' ], true)) {
            return false;
        }

        return extension_loaded('gd') && function_exists('imagewebp');
    }

    /**
     * Get quality level for JPEG (following Core Web Vitals best practices)
     * Lower quality = better compression = improved LCP
     *
     * @param string $level The compression level.
     * @return int
     */
    private function get_quality_level($level)
    {
        // Core Web Vitals recommends aggressive compression for better LCP
        // JPEG quality levels: low=80, medium=70, high=60
        $levels = [
            'low'    => 80,
            'medium' => 70,
            'high'   => 60,
        ];

        return $levels[ $level ] ?? 70;
    }

    /**
     * Get compression level for PNG (following Core Web Vitals best practices)
     *
     * @param string $level The compression level.
     * @return int
     */
    private function get_compression_level($level)
    {
        // PNG compression: 9 = maximum compression (best for Core Web Vitals)
        $levels = [
            'low'    => 7,
            'medium' => 8,
            'high'   => 9,
        ];

        return $levels[ $level ] ?? 8;
    }

    /**
     * Create a backup of the original file before optimization
     *
     * @param string $file_path The file path.
     * @param int    $attachment_id The attachment ID.
     * @return string The backup file path, or empty string if backup failed.
     */
    private function create_backup($file_path, $attachment_id)
    {
        $backup_dir = dirname($file_path) . '/.backups';

        if (! wp_mkdir_p($backup_dir)) {
            return '';
        }

        $file_info = pathinfo($file_path);
        $backup_file = $backup_dir . '/' . $file_info['filename'] . '-' . $attachment_id . '-backup.' . $file_info['extension'];

        // Only create backup if it doesn't already exist
        if (! file_exists($backup_file)) {
            if (! copy($file_path, $backup_file)) {
                return '';
            }
            
            // Fix permissions so www-data can read the backup during revert
            // Use 0644 (readable by all, writable by owner only)
            @chmod($backup_file, 0644);
        }

        return $backup_file;
    }

    /**
     * Optimize all attachment subsizes for responsive image compliance
     *
     * WordPress generates multiple image sizes (thumbnail, medium, large).
     * These need to be optimized for proper srcset generation and Lighthouse compliance.
     *
     * @param int $attachment_id The attachment ID.
     * @return bool Success flag.
     */
    protected function optimize_attachment_subsizes(int $attachment_id): bool
    {
        $metadata = wp_get_attachment_metadata($attachment_id);

        if (! isset($metadata['sizes']) || empty($metadata['sizes'])) {
            return true;  // No subsizes to optimize
        }

        $attached_file = get_attached_file($attachment_id);
        if (! $attached_file || ! file_exists($attached_file)) {
            return false;
        }

        $base_dir = dirname($attached_file);
        $optimized_count = 0;

        // Optimize each subsize
        foreach ($metadata['sizes'] as $size_name => $size_data) {
            $subsize_file = $base_dir . '/' . $size_data['file'];

            if (! file_exists($subsize_file)) {
                continue;  // Skip if file doesn't exist
            }

            // Optimize the subsize
            try {
                $result = $this->optimize_file($subsize_file, 'standard');

                if ($result) {
                    $optimized_count++;

                    // Create WebP version of subsize if configured
                    if ($this->config->should_create_webp()) {
                        if ($this->can_create_webp($subsize_file)) {
                            $this->create_webp_version($subsize_file);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue with other subsizes
                error_log("Failed to optimize subsize {$size_name}: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Revert an optimized image to its original backup
     *
     * @param int $attachment_id The attachment ID.
     * @return array
     */
    public function revert_optimization($attachment_id): Result
    {
        try {
            $file = get_attached_file($attachment_id);

            if (! $file || ! file_exists($file)) {
                return Result::failure('File not found: ' . ($file ?: 'no path'));
            }

            // Get backup file path
            $file_info = pathinfo($file);
            $backup_dir = dirname($file) . '/.backups';
            $backup_file = $backup_dir . '/' . $file_info['filename'] . '-' . $attachment_id . '-backup.' . $file_info['extension'];

            if (! file_exists($backup_file)) {
                return Result::failure('No backup found: ' . $backup_file);
            }

            // Check file permissions before attempting restore
            if (! is_readable($backup_file)) {
                // Try to fix permissions if backup is not readable
                @chmod($backup_file, 0644);
                
                // Check again after chmod attempt
                if (! is_readable($backup_file)) {
                    return Result::failure('Backup file is not readable and cannot fix permissions');
                }
            }

            if (! is_writable(dirname($file))) {
                return Result::failure('Cannot write to image directory');
            }

            $optimized_size = filesize($file);
            
            // Get image info from current (optimized) file
            $image_info = @getimagesize($file);
            $width = $image_info[0] ?? 0;
            $height = $image_info[1] ?? 0;
            
            // Create image context for hooks
            $context = new ImageContext(
                $file,
                (int) $attachment_id,
                [
                    'width' => $width,
                    'height' => $height,
                    'original_size' => $optimized_size,
                    'mime_type' => $image_info['mime'] ?? mime_content_type($file),
                    'backup_file' => $backup_file,
                ]
            );
            
            /**
             * Fires before reverting an optimization
             *
             * Allows plugins to perform cleanup or logging without re-reading files.
             * Can access backup file path via $context->get('backup_file')
             *
             * @param ImageContext $context Image context with metadata.
             */
            do_action('image_optimizer_before_revert', $context);

            // Restore from backup with detailed error handling
            $copy_result = @copy($backup_file, $file);
            
            if (! $copy_result) {
                $error_msg = 'Failed to copy backup file. ';
                if (function_exists('error_get_last')) {
                    $last_error = error_get_last();
                    if ($last_error) {
                        $error_msg .= 'PHP Error: ' . $last_error['message'];
                    }
                }
                return Result::failure($error_msg);
            }

            // Verify the restoration
            if (! file_exists($file) || filesize($file) === 0) {
                return Result::failure('Backup was copied but file is empty or missing');
            }

            $restored_size = filesize($file);

            // Delete WebP versions if they exist
            $this->delete_webp_version($file);

            // Update context with revert results
            $context->set('restored_size', $restored_size);
            $context->set('freed_space', $optimized_size - $restored_size);

            // Update optimization result to mark as reverted
            Database::save_optimization_result(
                $attachment_id,
                [
                    'original_size'  => $restored_size,
                    'optimized_size' => $optimized_size,
                    'compression_ratio' => 0,
                    'method'         => 'reverted',
                    'webp_available' => false,
                    'status'         => 'reverted',
                ],
            );
            
            /**
             * Fires after successful revert of optimization
             *
             * Allows plugins to perform post-revert actions or notifications.
             * Can access restored size via $context->get('restored_size')
             *
             * @param ImageContext $context Image context with revert metadata.
             */
            do_action('image_optimizer_after_revert', $context);

            return Result::success(
                [
                    'restored_size' => $restored_size,
                ],
                'Image successfully reverted to original'
            );
        } catch (\Exception $e) {
            return Result::from_exception($e);
        }
    }

    /**
     * Delete WebP version of an image
     *
     * @param string $file_path The file path.
     * @return bool
     */
    private function delete_webp_version($file_path)
    {
        $webp_file = $file_path . '.webp';

        if (file_exists($webp_file)) {
            return unlink($webp_file);
        }

        return true;
    }
}
