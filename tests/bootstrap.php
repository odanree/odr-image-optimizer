<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 *
 * Sets up the test environment for ImageOptimizer tests
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define plugin constants
if (!defined('IMAGE_OPTIMIZER_PATH')) {
    define('IMAGE_OPTIMIZER_PATH', dirname(__DIR__) . '/');
}
if (!defined('IMAGE_OPTIMIZER_URL')) {
    define('IMAGE_OPTIMIZER_URL', 'http://localhost/wp-content/plugins/odr-image-optimizer/');
}
if (!defined('IMAGE_OPTIMIZER_VERSION')) {
    define('IMAGE_OPTIMIZER_VERSION', '2.0.0-refactor');
}

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// ImageOptimizer autoloader
require_once dirname(__DIR__) . '/includes/class-autoloader.php';

\ImageOptimizer\Autoloader::register();
