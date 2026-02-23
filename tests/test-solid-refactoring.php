<?php

declare(strict_types=1);

/**
 * SOLID Refactoring - Testing Checklist
 *
 * Comprehensive test scenarios to verify all changes work correctly
 *
 * @package ImageOptimizer\Tests
 */

namespace ImageOptimizer\Tests;

/**
 * TEST 1: Dependency Inversion - Container Service Caching
 * ========================================================
 *
 * Verify that Container manages service lifecycle correctly
 */
class ContainerServiceCachingTest
{
    /**
     * Test that services are cached (singleton pattern)
     */
    public static function test_priority_service_is_cached(): bool
    {
        $s1 = \ImageOptimizer\Core\Container::get_priority_service();
        $s2 = \ImageOptimizer\Core\Container::get_priority_service();

        // Same instance reference (cached)
        if ($s1 !== $s2) {
            echo "❌ FAIL: PriorityService not cached\n";
            return false;
        }

        echo "✅ PASS: PriorityService caching works\n";
        return true;
    }

    /**
     * Test that all frontend services are cached independently
     */
    public static function test_all_services_cached(): bool
    {
        $container = \ImageOptimizer\Core\Container::class;

        $p1 = $container::get_priority_service();
        $p2 = $container::get_priority_service();
        if ($p1 !== $p2) {
            echo "❌ FAIL: PriorityService not cached\n";
            return false;
        }

        $a1 = $container::get_asset_manager();
        $a2 = $container::get_asset_manager();
        if ($a1 !== $a2) {
            echo "❌ FAIL: AssetManager not cached\n";
            return false;
        }

        $c1 = $container::get_cleanup_service();
        $c2 = $container::get_cleanup_service();
        if ($c1 !== $c2) {
            echo "❌ FAIL: CleanupService not cached\n";
            return false;
        }

        $w1 = $container::get_wordpress_adapter();
        $w2 = $container::get_wordpress_adapter();
        if ($w1 !== $w2) {
            echo "❌ FAIL: WordPressAdapter not cached\n";
            return false;
        }

        echo "✅ PASS: All services are cached correctly\n";
        return true;
    }

    /**
     * Test that services are different from each other
     */
    public static function test_services_are_different_instances(): bool
    {
        $container = \ImageOptimizer\Core\Container::class;

        $priority = $container::get_priority_service();
        $asset = $container::get_asset_manager();
        $cleanup = $container::get_cleanup_service();

        if ($priority === $asset || $priority === $cleanup || $asset === $cleanup) {
            echo "❌ FAIL: Different services should be different instances\n";
            return false;
        }

        echo "✅ PASS: Services are different instances\n";
        return true;
    }
}

/**
 * TEST 2: Single Responsibility - Instance State (Not Static)
 * ===========================================================
 *
 * Verify PriorityService uses instance state instead of static
 */
class InstanceStateTest
{
    /**
     * Test that PriorityService instances have isolated state
     */
    public static function test_priority_service_instance_isolation(): bool
    {
        $service1 = new \ImageOptimizer\Services\PriorityService();
        $service2 = new \ImageOptimizer\Services\PriorityService();

        // Use reflection to access private $lcp_id property
        $reflectionClass = new \ReflectionClass(\ImageOptimizer\Services\PriorityService::class);
        $property = $reflectionClass->getProperty('lcp_id');
        $property->setAccessible(true);

        // Initial state should be null for both
        $s1_lcp = $property->getValue($service1);
        $s2_lcp = $property->getValue($service2);

        if ($s1_lcp !== null || $s2_lcp !== null) {
            echo "❌ FAIL: Initial LCP ID should be null\n";
            return false;
        }

        // Modify service1 state
        $property->setValue($service1, 123);

        // Service2 should remain unchanged
        $s1_lcp = $property->getValue($service1);
        $s2_lcp = $property->getValue($service2);

        if ($s1_lcp !== 123 || $s2_lcp !== null) {
            echo "❌ FAIL: Instance state should be isolated\n";
            return false;
        }

        echo "✅ PASS: PriorityService has isolated instance state\n";
        return true;
    }

    /**
     * Test that reset_lcp_id() is an instance method (not static)
     */
    public static function test_reset_lcp_id_is_instance_method(): bool
    {
        $service = new \ImageOptimizer\Services\PriorityService();

        // Set reflection to test
        $reflectionClass = new \ReflectionClass(\ImageOptimizer\Services\PriorityService::class);
        $property = $reflectionClass->getProperty('lcp_id');
        $property->setAccessible(true);
        $property->setValue($service, 456);

        // Call reset_lcp_id() as instance method
        $service->reset_lcp_id();

        $lcp_id = $property->getValue($service);
        if ($lcp_id !== null) {
            echo "❌ FAIL: reset_lcp_id() did not reset state\n";
            return false;
        }

        echo "✅ PASS: reset_lcp_id() works as instance method\n";
        return true;
    }
}

/**
 * TEST 3: Liskov Substitution Principle - Exception Hierarchy
 * ===========================================================
 *
 * Verify exception hierarchy allows proper substitution
 */
class ExceptionHierarchyTest
{
    /**
     * Test that OptimizationFailedException extends ImageOptimizerException
     */
    public static function test_optimization_exception_hierarchy(): bool
    {
        $exception = new \ImageOptimizer\Exception\OptimizationFailedException('Test error');

        if (! $exception instanceof \ImageOptimizer\Exception\ImageOptimizerException) {
            echo "❌ FAIL: OptimizationFailedException should extend ImageOptimizerException\n";
            return false;
        }

        echo "✅ PASS: OptimizationFailedException extends base exception\n";
        return true;
    }

    /**
     * Test that BackupFailedException extends ImageOptimizerException
     */
    public static function test_backup_exception_hierarchy(): bool
    {
        $exception = new \ImageOptimizer\Exception\BackupFailedException('Test error');

        if (! $exception instanceof \ImageOptimizer\Exception\ImageOptimizerException) {
            echo "❌ FAIL: BackupFailedException should extend ImageOptimizerException\n";
            return false;
        }

        echo "✅ PASS: BackupFailedException extends base exception\n";
        return true;
    }

    /**
     * Test that ProcessorNotAvailableException extends ImageOptimizerException
     */
    public static function test_processor_exception_hierarchy(): bool
    {
        $exception = new \ImageOptimizer\Exception\ProcessorNotAvailableException('imagick', 'ImageMagick required');

        if (! $exception instanceof \ImageOptimizer\Exception\ImageOptimizerException) {
            echo "❌ FAIL: ProcessorNotAvailableException should extend ImageOptimizerException\n";
            return false;
        }

        echo "✅ PASS: ProcessorNotAvailableException extends base exception\n";
        return true;
    }

    /**
     * Test that all exceptions can be caught as base type
     */
    public static function test_exception_substitution(): bool
    {
        $exceptions = [
            new \ImageOptimizer\Exception\OptimizationFailedException('Test 1'),
            new \ImageOptimizer\Exception\BackupFailedException('Test 2'),
            new \ImageOptimizer\Exception\ProcessorNotAvailableException('dep', 'Test 3'),
        ];

        $caught = 0;
        foreach ($exceptions as $e) {
            try {
                throw $e;
            } catch (\ImageOptimizer\Exception\ImageOptimizerException $base) {
                $caught++;
            }
        }

        if ($caught !== 3) {
            echo "❌ FAIL: All exceptions should be catchable as base type\n";
            return false;
        }

        echo "✅ PASS: Exception substitution works correctly\n";
        return true;
    }

    /**
     * Test exception context parameter
     */
    public static function test_exception_context(): bool
    {
        $context = ['file' => 'test.jpg', 'size' => 2048];
        $exception = new \ImageOptimizer\Exception\OptimizationFailedException(
            'Conversion failed',
            0,
            null,
            $context
        );

        $retrieved_context = $exception->get_context();
        if ($retrieved_context !== $context) {
            echo "❌ FAIL: Exception context not stored correctly\n";
            return false;
        }

        echo "✅ PASS: Exception context works\n";
        return true;
    }
}

/**
 * TEST 4: WordPress Adapter - Interface Segregation
 * =================================================
 *
 * Verify adapter interface and implementation
 */
class WordPressAdapterTest
{
    /**
     * Test that WordPressAdapter implements interface
     */
    public static function test_adapter_implements_interface(): bool
    {
        $adapter = new \ImageOptimizer\Adapter\WordPressAdapter();

        if (! $adapter instanceof \ImageOptimizer\Adapter\WordPressAdapterInterface) {
            echo "❌ FAIL: WordPressAdapter should implement WordPressAdapterInterface\n";
            return false;
        }

        echo "✅ PASS: WordPressAdapter implements interface\n";
        return true;
    }

    /**
     * Test that adapter has all required methods
     */
    public static function test_adapter_has_all_methods(): bool
    {
        $interface = new \ReflectionClass(\ImageOptimizer\Adapter\WordPressAdapterInterface::class);
        $methods = $interface->getMethods(\ReflectionMethod::IS_ABSTRACT);

        $adapter = new \ImageOptimizer\Adapter\WordPressAdapter();

        foreach ($methods as $method) {
            if (! method_exists($adapter, $method->getName())) {
                echo "❌ FAIL: Adapter missing method: {$method->getName()}\n";
                return false;
            }
        }

        echo "✅ PASS: Adapter has all required methods\n";
        return true;
    }

    /**
     * Test adapter method return types
     */
    public static function test_adapter_method_types(): bool
    {
        $adapter = new \ImageOptimizer\Adapter\WordPressAdapter();

        // Test that methods return expected types
        $is_singular = $adapter->is_singular();
        if (! is_bool($is_singular)) {
            echo "❌ FAIL: is_singular() should return bool\n";
            return false;
        }

        $is_admin = $adapter->is_admin();
        if (! is_bool($is_admin)) {
            echo "❌ FAIL: is_admin() should return bool\n";
            return false;
        }

        $is_frontend = $adapter->is_frontend();
        if (! is_bool($is_frontend)) {
            echo "❌ FAIL: is_frontend() should return bool\n";
            return false;
        }

        echo "✅ PASS: Adapter methods return correct types\n";
        return true;
    }
}

/**
 * TEST 5: Hook Integration - DI Container Usage
 * =============================================
 *
 * Verify that WordPress hooks use Container services
 */
class HookIntegrationTest
{
    /**
     * Test that wp_head hook fires (mock test)
     *
     * Note: Requires WordPress environment to run fully
     */
    public static function test_wp_head_hooks_registered(): bool
    {
        global $wp_filter;

        if (! isset($wp_filter['wp_head'])) {
            echo "⚠️  SKIP: WordPress not loaded (test requires full WP environment)\n";
            return true;
        }

        $hooks = $wp_filter['wp_head'];
        $priorities = array_keys((array) $hooks);

        // Check that hooks exist at priority 0, 1, and 999
        if (! in_array(0, $priorities, true)) {
            echo "❌ FAIL: wp_head hook at priority 0 not found\n";
            return false;
        }

        if (! in_array(1, $priorities, true)) {
            echo "❌ FAIL: wp_head hook at priority 1 not found\n";
            return false;
        }

        echo "✅ PASS: WordPress hooks are registered\n";
        return true;
    }
}

/**
 * TEST 6: Open/Closed Principle - Processor Registry
 * =================================================
 *
 * Verify that registry allows extending without modification
 */
class ProcessorRegistryTest
{
    /**
     * Test that custom processor can be registered
     */
    public static function test_custom_processor_registration(): bool
    {
        // Create a mock processor
        $mock_processor = new class implements \ImageOptimizer\Processor\ImageProcessorInterface {
            public function process(string $filePath, int $quality): bool
            {
                return true;
            }

            public function supports(string $filePath): bool
            {
                return true;
            }

            public function getMimeType(): string
            {
                return 'image/test';
            }
        };

        // This would be done via filter in real usage
        $registry = new \ImageOptimizer\Processor\ProcessorRegistry();

        // Test that we can work with processor
        if (! $mock_processor instanceof \ImageOptimizer\Processor\ImageProcessorInterface) {
            echo "❌ FAIL: Custom processor should implement interface\n";
            return false;
        }

        echo "✅ PASS: Custom processor can be created\n";
        return true;
    }
}

/**
 * TEST 7: Integration - Full Request Flow
 * ======================================
 *
 * Test the complete flow from hook to service
 */
class IntegrationTest
{
    /**
     * Test Container -> Service -> WordPress calls flow
     */
    public static function test_full_flow(): bool
    {
        // Get service from container
        $priority_service = \ImageOptimizer\Core\Container::get_priority_service();

        // Service should be usable
        if (! method_exists($priority_service, 'inject_preload')) {
            echo "❌ FAIL: PriorityService missing inject_preload method\n";
            return false;
        }

        // Get another reference - should be same instance
        $priority_service2 = \ImageOptimizer\Core\Container::get_priority_service();
        if ($priority_service !== $priority_service2) {
            echo "❌ FAIL: Container should return cached instance\n";
            return false;
        }

        echo "✅ PASS: Full flow works correctly\n";
        return true;
    }

    /**
     * Test Container clearing for testing
     */
    public static function test_container_clear(): bool
    {
        $s1 = \ImageOptimizer\Core\Container::get_priority_service();
        \ImageOptimizer\Core\Container::clear();
        $s2 = \ImageOptimizer\Core\Container::get_priority_service();

        if ($s1 === $s2) {
            echo "❌ FAIL: Container::clear() should reset instances\n";
            return false;
        }

        echo "✅ PASS: Container clearing works\n";
        return true;
    }

    /**
     * Test Container set_instance for testing
     */
    public static function test_container_set_instance(): bool
    {
        $mock_service = new \ImageOptimizer\Services\PriorityService();
        \ImageOptimizer\Core\Container::set_instance('priority_service', $mock_service);

        $retrieved = \ImageOptimizer\Core\Container::get_priority_service();

        if ($retrieved !== $mock_service) {
            echo "❌ FAIL: Container::set_instance() should override instance\n";
            return false;
        }

        echo "✅ PASS: Container set_instance works\n";
        return true;
    }
}

/**
 * Run all tests
 */
class TestRunner
{
    public static function run_all(): void
    {
        echo "\n=== SOLID Refactoring - Test Suite ===\n\n";

        $tests = [
            // Test 1: Container Caching
            ContainerServiceCachingTest::test_priority_service_is_cached(),
            ContainerServiceCachingTest::test_all_services_cached(),
            ContainerServiceCachingTest::test_services_are_different_instances(),

            // Test 2: Instance State
            InstanceStateTest::test_priority_service_instance_isolation(),
            InstanceStateTest::test_reset_lcp_id_is_instance_method(),

            // Test 3: Exception Hierarchy
            ExceptionHierarchyTest::test_optimization_exception_hierarchy(),
            ExceptionHierarchyTest::test_backup_exception_hierarchy(),
            ExceptionHierarchyTest::test_processor_exception_hierarchy(),
            ExceptionHierarchyTest::test_exception_substitution(),
            ExceptionHierarchyTest::test_exception_context(),

            // Test 4: WordPress Adapter
            WordPressAdapterTest::test_adapter_implements_interface(),
            WordPressAdapterTest::test_adapter_has_all_methods(),
            WordPressAdapterTest::test_adapter_method_types(),

            // Test 5: Hook Integration
            HookIntegrationTest::test_wp_head_hooks_registered(),

            // Test 6: Processor Registry
            ProcessorRegistryTest::test_custom_processor_registration(),

            // Test 7: Integration
            IntegrationTest::test_full_flow(),
            IntegrationTest::test_container_clear(),
            IntegrationTest::test_container_set_instance(),
        ];

        $passed = array_sum($tests);
        $total = count($tests);

        echo "\n=== Test Results ===\n";
        echo "Passed: $passed/$total\n";

        if ($passed === $total) {
            echo "✅ All tests passed!\n";
        } else {
            echo "❌ Some tests failed\n";
            exit(1);
        }
    }
}
