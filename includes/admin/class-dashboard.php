<?php

declare(strict_types=1);

/**
 * Admin Dashboard
 *
 * @package ImageOptimizer
 * @author  Danh Le
 */

namespace ImageOptimizer\Admin;

if (! defined('ABSPATH')) {
    exit('Direct access denied.');
}


/**
 * Dashboard class
 */
class Dashboard
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ]);
    }

    /**
     * Enqueue scripts and styles
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts($hook)
    {
        // Hook name for top-level page: toplevel_page_{page_slug}
        if ('toplevel_page_image-optimizer' !== $hook) {
            return;
        }

        // Use current time as cache buster with microtime for maximum uniqueness
        $cache_buster = str_replace('.', '', (string) microtime(true));

        wp_enqueue_style(
            'image-optimizer-dashboard',
            IMAGE_OPTIMIZER_URL . 'assets/css/dashboard.css?t=' . $cache_buster,
            [],
            false,
        );

        // Also enqueue settings styles for the settings tab
        wp_enqueue_style(
            'odr-image-optimizer-settings',
            IMAGE_OPTIMIZER_URL . 'assets/css/settings.css',
            [],
            IMAGE_OPTIMIZER_VERSION,
        );

        wp_enqueue_script(
            'image-optimizer-dashboard',
            IMAGE_OPTIMIZER_URL . 'assets/js/dashboard.js?t=' . $cache_buster,
            [],
            false,
            true,
        );

        wp_localize_script(
            'image-optimizer-dashboard',
            'imageOptimizerData',
            [
                'rest_nonce' => wp_create_nonce('wp_rest'),
                'rest_url' => rest_url('image-optimizer/v1/'),
                'ajax_nonce' => wp_create_nonce('image_optimizer_nonce'),
            ],
        );
    }

    /**
     * Render dashboard
     */
    public static function render()
    {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
		<div class="wrap image-optimizer-wrap">
			<h1><?php esc_html_e('ODR Image Optimizer', 'odr-image-optimizer'); ?></h1>
			
			<!-- Tab Navigation -->
			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url(admin_url('admin.php?page=image-optimizer&tab=overview')); ?>" class="nav-tab <?php echo $tab === 'overview' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Dashboard', 'odr-image-optimizer'); ?>
				</a>
				<a href="<?php echo esc_url(admin_url('admin.php?page=image-optimizer&tab=tools')); ?>" class="nav-tab <?php echo $tab === 'tools' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Tools', 'odr-image-optimizer'); ?>
				</a>
				<a href="<?php echo esc_url(admin_url('admin.php?page=image-optimizer&tab=settings')); ?>" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Settings', 'odr-image-optimizer'); ?>
				</a>
			</nav>

		<!-- Tab Content -->
		<div class="tab-content">
			<?php if ('settings' === $tab) : ?>
				<?php Settings::render(); ?>
			<?php elseif ('tools' === $tab) : ?>
				<?php self::render_tools(); ?>
			<?php else : ?>
				<div id="image-optimizer-dashboard"></div>
			<?php endif; ?>
		</div>
		</div>
		<?php
    }

    /**
     * Render tools tab
     */
    public static function render_tools()
    {
        ?>
		<div class="tools-section">
			<h2><?php esc_html_e('Bulk Optimization Tools', 'odr-image-optimizer'); ?></h2>
			<p><?php esc_html_e('Retroactively optimize existing images in your media library.', 'odr-image-optimizer'); ?></p>
			
			<div class="tool-card">
				<h3><?php esc_html_e('Optimize Existing Images', 'odr-image-optimizer'); ?></h3>
				<p><?php esc_html_e('Scan and optimize all unoptimized images uploaded before the plugin was installed.', 'odr-image-optimizer'); ?></p>
				<button id="bulk-optimize-btn" class="button button-primary"><?php esc_html_e('Start Bulk Optimization', 'odr-image-optimizer'); ?></button>
				<div id="bulk-optimize-progress" style="display:none; margin-top: 20px;">
					<progress id="progress-bar" value="0" max="100" style="width: 100%; height: 25px;"></progress>
					<p><span id="progress-text">0</span>% complete | <span id="progress-count">0 / 0</span> images</p>
				</div>
				<div id="bulk-optimize-results" style="display:none; margin-top: 20px;">
					<h4><?php esc_html_e('Results:', 'odr-image-optimizer'); ?></h4>
					<p><span id="results-success">0</span> images optimized</p>
					<p><span id="results-failed">0</span> images failed</p>
				</div>
			</div>
		</div>

		<style>
			.tools-section {
				max-width: 600px;
				margin: 20px 0;
			}
			.tool-card {
				background: #fff;
				border: 1px solid #ccc;
				border-radius: 4px;
				padding: 20px;
				margin-top: 15px;
			}
			.tool-card h3 {
				margin-top: 0;
			}
		</style>

		<script>
			(function() {
				const bulkOptimizeBtn = document.getElementById('bulk-optimize-btn');
				const progressDiv = document.getElementById('bulk-optimize-progress');
				const progressBar = document.getElementById('progress-bar');
				const progressText = document.getElementById('progress-text');
				const progressCount = document.getElementById('progress-count');
				const resultsDiv = document.getElementById('bulk-optimize-results');
				const resultsSuccess = document.getElementById('results-success');
				const resultsFailed = document.getElementById('results-failed');

				bulkOptimizeBtn.addEventListener('click', async function() {
					bulkOptimizeBtn.disabled = true;
					progressDiv.style.display = 'block';
					resultsDiv.style.display = 'none';

					try {
						// Get all media attachments
						const attachments = await fetch(imageOptimizerData.rest_url + 'media/bulk', {
							headers: {
								'X-WP-Nonce': imageOptimizerData.rest_nonce,
							},
						}).then(r => r.json());

						console.log('Bulk media response:', attachments);						if (!attachments || attachments.length === 0) {
							alert('No images found to optimize');
							bulkOptimizeBtn.disabled = false;
							progressDiv.style.display = 'none';
							return;
						}

						// Process images one at a time with streaming updates
						let successful = 0;
						let failed = 0;

						for (let i = 0; i < attachments.length; i++) {
							const attachmentId = attachments[i].id;
							
							try {
								const response = await fetch(
									'/wp-json/image-optimizer/v1/optimize/' + attachmentId,
									{
										method: 'POST',
										headers: {
											'X-WP-Nonce': imageOptimizerData.rest_nonce,
											'Content-Type': 'application/json',
										},
									}
								);
								
								const data = await response.json();
								console.log(`Image ${attachmentId} response:`, response.status, data);
								if (response.ok && data.success) {
									successful++;
								} else {
									failed++;
								}
							} catch (e) {
								console.error(`Image ${attachmentId} error:`, e);
								failed++;
							}

							// Update progress in real-time after EACH image
							const progress = Math.round(((i + 1) / attachments.length) * 100);
							progressBar.value = progress;
							progressText.textContent = progress;
							progressCount.textContent = (i + 1) + ' / ' + attachments.length;
						}

						// Show final results
						progressDiv.style.display = 'none';
						resultsDiv.style.display = 'block';
						resultsSuccess.textContent = successful;
						resultsFailed.textContent = failed;

					} catch (error) {
						alert('Error during bulk optimization: ' + error.message);
						progressDiv.style.display = 'none';
						bulkOptimizeBtn.disabled = false;
					}

					bulkOptimizeBtn.disabled = false;
				});
			})();
		</script>
		<?php
    }
}
