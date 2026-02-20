<?php

declare(strict_types=1);




if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Responsive Images - Generate srcset and sizes attributes for optimized images
 *
 * @package ImageOptimizer\Frontend
 * @author  Danh Le
 */

namespace ImageOptimizer\Frontend;

/**
 * Responsive Images class - Handles srcset generation for WebP images
 */
class ResponsiveImages
{
    private bool $enabled = true;

    /**
     * Standard WordPress image sizes to generate srcset for
     *
     * @var array
     */
    private array $image_sizes = [
        'thumbnail'    => [ 150, 150 ],
        'medium'       => [ 300, 300 ],
        'large'        => [ 1024, 1024 ],
        'full'         => [ 2048, 2048 ],
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void
    {
        // Check if responsive images are enabled in settings
        $settings = get_option('image_optimizer_settings', []);
        $this->enabled = ! empty($settings['enable_responsive_images']);

        if (!$this->enabled) {
            return;
        }

        // Hook into image generation to add srcset
        add_filter('wp_get_attachment_image_attributes', [ $this, 'add_srcset' ], 10, 3);

        // Hook into image tag to add loading="lazy" for LCP improvement
        add_filter('wp_img_tag_add_loading_attr', [ $this, 'add_loading_attr' ], 10, 3);
    }

    /**
     * Add srcset attribute to image tags
     *
     * @param array $attr Image attributes.
     * @param object $attachment The attachment object.
     * @param string|array $size The image size.
     * @return array
     */
    public function add_srcset(array $attr, $attachment, $size): array
    {
        if (empty($attachment->ID)) {
            return $attr;
        }

        $attachment_id = $attachment->ID;
        $srcset = $this->generate_srcset($attachment_id);

        if (!empty($srcset)) {
            $attr['srcset'] = $srcset;

            // Add sizes attribute for better browser optimization
            $attr['sizes'] = $this->get_sizes_attribute();
        }

        return $attr;
    }

    /**
     * Add loading="lazy" attribute for performance
     *
     * @param string|bool $loading_attr Current loading attribute.
     * @param string $tag_name Tag name.
     * @param array $attr Image attributes.
     * @return string|bool
     */
    public function add_loading_attr($loading_attr, $tag_name, $attr)
    {
        // Don't lazy-load images in the header or hero sections
        // Only apply to below-the-fold images
        if (!isset($attr['class']) || !preg_match('/wp-image-|attachment-/i', $attr['class'])) {
            return $loading_attr;
        }

        // Return lazy loading for standard images
        return 'lazy';
    }

    /**
     * Generate srcset for an attachment
     *
     * @param int $attachment_id The attachment ID.
     * @return string
     */
    private function generate_srcset(int $attachment_id): string
    {
        $srcset = [];

        // Get the base file path
        $file = get_attached_file($attachment_id);
        if (!$file) {
            return '';
        }

        // Generate URLs for different sizes
        foreach ($this->image_sizes as $size_name => $dimensions) {
            $metadata = wp_get_attachment_metadata($attachment_id);

            if (empty($metadata['sizes'])) {
                continue;
            }

            // Check if this size exists
            if ($size_name !== 'full' && isset($metadata['sizes'][ $size_name ])) {
                $size_file = $metadata['sizes'][ $size_name ]['file'];
                $size_dir = dirname($file);
                $size_path = $size_dir . '/' . $size_file;

                // Check if WebP version exists for this size
                if (file_exists($size_path . '.webp')) {
                    $url = dirname(wp_get_attachment_url($attachment_id)) . '/' . $size_file . '.webp';
                    $width = $metadata['sizes'][ $size_name ]['width'] ?? 0;
                    if ($width) {
                        $srcset[] = $url . ' ' . $width . 'w';
                    }
                }
            } elseif ($size_name === 'full') {
                // Full size
                $url = wp_get_attachment_url($attachment_id);

                // Use WebP if available
                if (file_exists($file . '.webp')) {
                    $url = $url . '.webp';
                }

                $width = $metadata['width'] ?? 0;
                if ($width) {
                    $srcset[] = $url . ' ' . $width . 'w';
                }
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Get sizes attribute value
     *
     * @return string
     */
    private function get_sizes_attribute(): string
    {
        // Common responsive breakpoints
        return '(max-width: 480px) 100vw, (max-width: 768px) 90vw, (max-width: 1024px) 80vw, 70vw';
    }

    /**
     * Enable responsive images
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable responsive images
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if responsive images are enabled
     *
     * @return bool
     */
    public function is_enabled(): bool
    {
        return $this->enabled;
    }
}
