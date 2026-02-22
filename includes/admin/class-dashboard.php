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

use ImageOptimizer\Admin\Settings;

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
                'nonce' => wp_create_nonce('wp_rest'),
                'rest_url' => rest_url('image-optimizer/v1/'),
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
				<a href="<?php echo esc_url(admin_url('admin.php?page=image-optimizer&tab=settings')); ?>" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e('Settings', 'odr-image-optimizer'); ?>
				</a>
			</nav>

		<!-- Tab Content -->
		<div class="tab-content">
			<?php if ('settings' === $tab) : ?>
				<?php Settings::render(); ?>
			<?php else : ?>
				<div id="image-optimizer-dashboard"></div>
			<?php endif; ?>
		</div>
		</div>
		<?php
    }
}
