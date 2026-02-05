<?php
declare(strict_types=1);

/**
 * PHPUnit Bootstrap
 *
 * Sets up the test environment for ImageOptimizer tests
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';
