#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Quick Test Runner - SOLID Refactoring Verification
 *
 * Usage: php run-tests.php [test-category]
 *
 * Categories:
 * - container: Test DI Container caching
 * - state: Test instance state isolation
 * - exceptions: Test exception hierarchy
 * - adapter: Test WordPress adapter
 * - all: Run all tests (default)
 */

// Define plugin constants
define('IMAGE_OPTIMIZER_VERSION', time());
define('IMAGE_OPTIMIZER_PATH', __DIR__ . '/');
define('IMAGE_OPTIMIZER_URL', '');
define('ABSPATH', __DIR__ . '/../../../../');

// Load plugin autoloader
require_once __DIR__ . '/includes/class-autoloader.php';
\ImageOptimizer\Autoloader::register();

class QuickTests {
    private static $passed = 0;
    private static $failed = 0;

    public static function run($category = 'all'): void {
        echo "\n=== SOLID Refactoring Tests ===\n\n";

        match($category) {
            'container' => self::test_container(),
            'state' => self::test_state(),
            'exceptions' => self::test_exceptions(),
            'adapter' => self::test_adapter(),
            'all' => self::test_all(),
            default => self::usage(),
        };

        echo "\n=== Results ===\n";
        echo "Passed: " . self::$passed . "\n";
        echo "Failed: " . self::$failed . "\n\n";

        if (self::$failed > 0) {
            exit(1);
        }
    }

    private static function test_all(): void {
        self::test_container();
        self::test_state();
        self::test_exceptions();
        self::test_adapter();
    }

    private static function test_container(): void {
        echo "📦 Container Tests\n";
        echo "────────────────────────────────\n";

        // Test 1: Priority service caching
        $s1 = \ImageOptimizer\Core\Container::get_priority_service();
        $s2 = \ImageOptimizer\Core\Container::get_priority_service();

        if ($s1 === $s2) {
            self::pass("PriorityService caching");
        } else {
            self::fail("PriorityService caching - not same instance");
        }

        // Test 2: Asset manager caching
        $a1 = \ImageOptimizer\Core\Container::get_asset_manager();
        $a2 = \ImageOptimizer\Core\Container::get_asset_manager();

        if ($a1 === $a2) {
            self::pass("AssetManager caching");
        } else {
            self::fail("AssetManager caching");
        }

        // Test 3: Cleanup service caching
        $c1 = \ImageOptimizer\Core\Container::get_cleanup_service();
        $c2 = \ImageOptimizer\Core\Container::get_cleanup_service();

        if ($c1 === $c2) {
            self::pass("CleanupService caching");
        } else {
            self::fail("CleanupService caching");
        }

        // Test 4: WordPress adapter caching
        $w1 = \ImageOptimizer\Core\Container::get_wordpress_adapter();
        $w2 = \ImageOptimizer\Core\Container::get_wordpress_adapter();

        if ($w1 === $w2) {
            self::pass("WordPressAdapter caching");
        } else {
            self::fail("WordPressAdapter caching");
        }

        // Test 5: Different services are different instances
        if ($s1 !== $a1 && $a1 !== $c1 && $c1 !== $w1) {
            self::pass("Different services are different instances");
        } else {
            self::fail("Different services should be different objects");
        }

        // Test 6: Container::clear() resets instances
        $before = \ImageOptimizer\Core\Container::get_priority_service();
        \ImageOptimizer\Core\Container::clear();
        $after = \ImageOptimizer\Core\Container::get_priority_service();

        if ($before !== $after) {
            self::pass("Container::clear() resets instances");
        } else {
            self::fail("Container::clear() should reset instances");
        }

        // Test 7: Container::set_instance() works
        $mock = new \ImageOptimizer\Services\PriorityService();
        \ImageOptimizer\Core\Container::set_instance('priority_service', $mock);
        $retrieved = \ImageOptimizer\Core\Container::get_priority_service();

        if ($retrieved === $mock) {
            self::pass("Container::set_instance() works");
        } else {
            self::fail("Container::set_instance() should override instance");
        }

        echo "\n";
    }

    private static function test_state(): void {
        echo "🔄 Instance State Tests\n";
        echo "────────────────────────────────\n";

        // Test 1: Create two independent instances
        $service1 = new \ImageOptimizer\Services\PriorityService();
        $service2 = new \ImageOptimizer\Services\PriorityService();

        if ($service1 !== $service2) {
            self::pass("Can create multiple instances");
        } else {
            self::fail("Should be able to create independent instances");
        }

        // Test 2: Check property is instance variable
        $ref = new \ReflectionClass(\ImageOptimizer\Services\PriorityService::class);
        $has_static = false;

        foreach ($ref->getProperties(\ReflectionProperty::IS_STATIC) as $prop) {
            if ($prop->getName() === 'lcp_id') {
                $has_static = true;
            }
        }

        if (!$has_static) {
            self::pass("lcp_id is instance variable (not static)");
        } else {
            self::fail("lcp_id should not be static");
        }

        // Test 3: State isolation
        $prop = $ref->getProperty('lcp_id');
        $prop->setAccessible(true);

        $prop->setValue($service1, 123);
        $val1 = $prop->getValue($service1);
        $val2 = $prop->getValue($service2);

        if ($val1 === 123 && $val2 === null) {
            self::pass("Instance state is isolated");
        } else {
            self::fail("Each instance should have isolated state");
        }

        // Test 4: Reset works
        $method = $ref->getMethod('reset_lcp_id');
        $method->invoke($service1);
        $val_reset = $prop->getValue($service1);

        if ($val_reset === null) {
            self::pass("reset_lcp_id() clears state");
        } else {
            self::fail("reset_lcp_id() should set lcp_id to null");
        }

        echo "\n";
    }

    private static function test_exceptions(): void {
        echo "🚨 Exception Hierarchy Tests\n";
        echo "────────────────────────────────\n";

        // Test 1: OptimizationFailedException extends base
        $ex1 = new \ImageOptimizer\Exception\OptimizationFailedException('Test');
        if ($ex1 instanceof \ImageOptimizer\Exception\ImageOptimizerException) {
            self::pass("OptimizationFailedException extends base");
        } else {
            self::fail("OptimizationFailedException should extend ImageOptimizerException");
        }

        // Test 2: BackupFailedException extends base
        $ex2 = new \ImageOptimizer\Exception\BackupFailedException('Test');
        if ($ex2 instanceof \ImageOptimizer\Exception\ImageOptimizerException) {
            self::pass("BackupFailedException extends base");
        } else {
            self::fail("BackupFailedException should extend ImageOptimizerException");
        }

        // Test 3: ProcessorNotAvailableException extends base
        $ex3 = new \ImageOptimizer\Exception\ProcessorNotAvailableException('test', 'Test');
        if ($ex3 instanceof \ImageOptimizer\Exception\ImageOptimizerException) {
            self::pass("ProcessorNotAvailableException extends base");
        } else {
            self::fail("ProcessorNotAvailableException should extend ImageOptimizerException");
        }

        // Test 4: Liskov Substitution - catch as base
        $caught = 0;
        $exceptions = [
            new \ImageOptimizer\Exception\OptimizationFailedException('1'),
            new \ImageOptimizer\Exception\BackupFailedException('2'),
            new \ImageOptimizer\Exception\ProcessorNotAvailableException('dep', '3'),
        ];

        foreach ($exceptions as $e) {
            try {
                throw $e;
            } catch (\ImageOptimizer\Exception\ImageOptimizerException $base) {
                $caught++;
            }
        }

        if ($caught === 3) {
            self::pass("All exceptions catchable as base type (LSP)");
        } else {
            self::fail("All exceptions should be catchable as base type");
        }

        // Test 5: Exception context
        $context = ['file' => 'test.jpg', 'size' => 1024];
        $ex = new \ImageOptimizer\Exception\OptimizationFailedException(
            'Test',
            0,
            null,
            $context
        );

        if ($ex->get_context() === $context) {
            self::pass("Exception context storage works");
        } else {
            self::fail("Exception context should be retrievable");
        }

        echo "\n";
    }

    private static function test_adapter(): void {
        echo "🔌 WordPress Adapter Tests\n";
        echo "────────────────────────────────\n";

        // Test 1: Adapter implements interface
        $adapter = new \ImageOptimizer\Adapter\WordPressAdapter();
        if ($adapter instanceof \ImageOptimizer\Adapter\WordPressAdapterInterface) {
            self::pass("WordPressAdapter implements interface");
        } else {
            self::fail("WordPressAdapter should implement WordPressAdapterInterface");
        }

        // Test 2: Has all required methods
        $interface = new \ReflectionClass(\ImageOptimizer\Adapter\WordPressAdapterInterface::class);
        $required_methods = [];

        foreach ($interface->getMethods(\ReflectionMethod::IS_ABSTRACT) as $method) {
            $required_methods[] = $method->getName();
        }

        $missing = [];
        foreach ($required_methods as $method_name) {
            if (!method_exists($adapter, $method_name)) {
                $missing[] = $method_name;
            }
        }

        if (empty($missing)) {
            self::pass("Adapter has all " . count($required_methods) . " required methods");
        } else {
            self::fail("Adapter missing methods: " . implode(', ', $missing));
        }

        // Test 3: Methods return correct types
        $is_bool_methods = ['is_singular', 'is_admin', 'is_frontend', 'dequeue_script'];
        $type_ok = true;

        foreach ($is_bool_methods as $method) {
            $result = $adapter->$method();
            if (!is_bool($result)) {
                $type_ok = false;
                break;
            }
        }

        if ($type_ok) {
            self::pass("Adapter methods return correct types");
        } else {
            self::fail("Adapter method return types incorrect");
        }

        // Test 4: is_frontend = !is_admin
        $is_frontend = $adapter->is_frontend();
        $is_admin = $adapter->is_admin();

        if ($is_frontend === !$is_admin) {
            self::pass("is_frontend() correctly inverts is_admin()");
        } else {
            self::fail("is_frontend() should be inverse of is_admin()");
        }

        echo "\n";
    }

    private static function pass($message): void {
        echo "✅ $message\n";
        self::$passed++;
    }

    private static function fail($message): void {
        echo "❌ $message\n";
        self::$failed++;
    }

    private static function usage(): void {
        echo "Usage: php run-tests.php [category]\n\n";
        echo "Categories:\n";
        echo "  container  - Test DI Container\n";
        echo "  state      - Test instance state\n";
        echo "  exceptions - Test exception hierarchy\n";
        echo "  adapter    - Test WordPress adapter\n";
        echo "  all        - Run all tests (default)\n";
    }
}

// Run tests
$category = isset($argv[1]) ? $argv[1] : 'all';
QuickTests::run($category);
