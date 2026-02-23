# SOLID Refactoring - Testing Checklist

Complete testing plan to verify all changes work correctly.

## Quick Start

```bash
# Run the test suite
cd /Users/odanree/Documents/Projects/wordpress-local/wp-content/plugins/odr-image-optimizer
php -d error_reporting=E_ALL tests/test-solid-refactoring.php
```

---

## Test Categories

### 1. ✅ Dependency Injection Container Tests

**Goal:** Verify Container manages services correctly

| Test | Command | Expected Result |
|------|---------|---|
| **Service Caching** | `$s1 = Container::get_priority_service(); $s2 = Container::get_priority_service();` | `$s1 === $s2` (same reference) |
| **Multiple Services** | Get all 4 services: priority, asset, cleanup, adapter | Each cached independently |
| **Service Uniqueness** | `$p !== $a && $p !== $c` | Different services are different objects |
| **Container Clear** | `Container::clear(); $new = Container::get_priority_service();` | New instance created after clear |
| **Set Instance** | `Container::set_instance('priority_service', $mock);` | Mock instance returned next call |

**Test File:** `tests/test-solid-refactoring.php::ContainerServiceCachingTest`

**Run:**
```bash
php -r "require 'tests/test-solid-refactoring.php'; ImageOptimizer\Tests\ContainerServiceCachingTest::test_priority_service_is_cached();"
```

---

### 2. ✅ Instance State (Not Static) Tests

**Goal:** Verify PriorityService uses instance variables instead of static state

| Test | How to Verify | Expected Result |
|------|---|---|
| **State Isolation** | Create 2 PriorityService instances, set LCP ID on one | Other instance unaffected |
| **Reset Works** | Call `$service->reset_lcp_id()` | LCP ID set to null |
| **No Class Variables** | Check PHP reflection for `static` keyword on `$lcp_id` | No `static` keyword found |

**Test File:** `tests/test-solid-refactoring.php::InstanceStateTest`

**Manual Verification:**
```php
// Open WordPress WP-CLI or PHP shell
$s1 = new \ImageOptimizer\Services\PriorityService();
$s2 = new \ImageOptimizer\Services\PriorityService();

// Use reflection to access private property
$ref = new \ReflectionClass($s1);
$prop = $ref->getProperty('lcp_id');
$prop->setAccessible(true);

// Modify s1's state
$prop->setValue($s1, 123);

// s2 should be unchanged
echo $prop->getValue($s1);  // 123
echo $prop->getValue($s2);  // null ✅
```

---

### 3. ✅ Exception Hierarchy Tests

**Goal:** Verify Liskov Substitution Principle with exception hierarchy

| Test | How to Verify | Expected Result |
|------|---|---|
| **OptimizationFailedException extends base** | `instanceof ImageOptimizerException` | True |
| **BackupFailedException extends base** | `instanceof ImageOptimizerException` | True |
| **ProcessorNotAvailableException extends base** | `instanceof ImageOptimizerException` | True |
| **Catch as base type** | Throw each exception, catch as base | All caught successfully |
| **Exception context** | Create with context array, retrieve it | Context matches |

**Test File:** `tests/test-solid-refactoring.php::ExceptionHierarchyTest`

**Manual Verification:**
```php
// Test exception substitution
try {
    throw new \ImageOptimizer\Exception\OptimizationFailedException('Test');
} catch (\ImageOptimizer\Exception\ImageOptimizerException $e) {
    echo "Caught as base exception ✅\n";
}

// Test exception context
$ex = new \ImageOptimizer\Exception\OptimizationFailedException(
    'Test',
    0,
    null,
    ['file' => 'test.jpg', 'quality' => 80]
);
$context = $ex->get_context();
echo $context['file'];  // test.jpg ✅
```

---

### 4. ✅ WordPress Adapter Tests

**Goal:** Verify adapter interface and implementation

| Test | How to Verify | Expected Result |
|------|---|---|
| **Adapter implements interface** | `instanceof WordPressAdapterInterface` | True |
| **Has all 9 methods** | Check via reflection | All methods exist |
| **Method return types** | Call methods, check types | Correct types returned |
| **is_frontend() = !is_admin()** | In admin vs frontend | Inverse relationship |

**Test File:** `tests/test-solid-refactoring.php::WordPressAdapterTest`

**Manual Verification:**
```php
// In WordPress WP-CLI or admin
$adapter = new \ImageOptimizer\Adapter\WordPressAdapter();

// Test return types
var_dump($adapter->is_singular());           // bool ✅
var_dump($adapter->is_admin());              // bool ✅
var_dump($adapter->is_frontend());           // bool ✅

// Test frontend relationship
echo $adapter->is_admin() === !$adapter->is_frontend() ? "✅" : "❌";
```

---

### 5. ✅ Plugin Hook Integration Tests

**Goal:** Verify WordPress hooks use Container services

| Test | How to Verify | Expected Result |
|------|---|---|
| **wp_head priority 0** | Check if hook exists | Hook registered ✅ |
| **wp_head priority 1** | Check if hook exists | Hook registered ✅ |
| **wp_enqueue_scripts priority 999** | Check if hook exists | Hook registered ✅ |
| **Hooks call Container methods** | Check `odr-image-optimizer.php` | No direct `new Service()` calls |

**Manual Verification - Check Hook Syntax:**
```bash
# Search for direct instantiation (should NOT exist)
grep -n "new \\\\ImageOptimizer\\\\Services\\\\" odr-image-optimizer.php

# Should return: no matches ✅

# Search for Container usage (SHOULD exist)
grep -n "Container::get_" odr-image-optimizer.php

# Should return 4 matches ✅
```

**WordPress Test (requires running site):**
```bash
# Check what's hooked to wp_head at priority 0
wp eval 'global $wp_filter; print_r($wp_filter["wp_head"][0]);'

# Should show PriorityService::override_font_display ✅
```

---

### 6. ✅ Open/Closed Principle - Processor Registry

**Goal:** Verify registry allows extending without modifying core

| Test | How to Verify | Expected Result |
|------|---|---|
| **Custom processor can implement interface** | Create mock processor | Implements ImageProcessorInterface |
| **Registry pattern documented** | Check `docs/EXTENDING.md` | Complete walkthrough with AVIF example |
| **No hardcoded processors** | Check ProcessorRegistry | Uses morph map pattern |

**Manual Verification:**
```php
// Create custom processor (from docs/EXTENDING.md example)
class AvifProcessor implements \ImageOptimizer\Processor\ImageProcessorInterface {
    public function process(string $filePath, int $quality): bool { return true; }
    public function supports(string $filePath): bool { return true; }
    public function getMimeType(): string { return 'image/avif'; }
}

// Should work without modifying plugin code ✅
```

---

### 7. ✅ Full Integration Tests

**Goal:** End-to-end verification of refactored system

| Test | How to Verify | Expected Result |
|------|---|---|
| **Container to Service to WordPress** | Service method that calls WordPress function | Works without modification |
| **No static state pollution** | Multiple requests | Each request has clean state |
| **Exception handling** | Processor throws OptimizationFailedException | Caught and handled correctly |

**Manual Test - Frontend Page Load:**
```bash
# 1. Navigate to a WordPress page with featured image
# 2. Check browser console for no JS errors
# 3. Check page source for preload link
grep 'rel="preload"' /tmp/page_source.html

# Should find preload link ✅
```

**Manual Test - Exception Handling:**
```php
// In WordPress environment
try {
    throw new \ImageOptimizer\Exception\OptimizationFailedException('Test failure');
} catch (\ImageOptimizer\Exception\ImageOptimizerException $e) {
    echo "Exception caught correctly\n";  // ✅
    echo $e->getMessage();                // Test failure ✅
}
```

---

## Running Tests

### Option 1: Quick Unit Tests (No WordPress Required)

```bash
cd /Users/odanree/Documents/Projects/wordpress-local/wp-content/plugins/odr-image-optimizer
php tests/test-solid-refactoring.php
```

**Expected Output:**
```
=== SOLID Refactoring - Test Suite ===

✅ PASS: PriorityService caching works
✅ PASS: All services are cached correctly
✅ PASS: Services are different instances
✅ PASS: PriorityService has isolated instance state
✅ PASS: reset_lcp_id() works as instance method
✅ PASS: OptimizationFailedException extends base exception
✅ PASS: BackupFailedException extends base exception
✅ PASS: ProcessorNotAvailableException extends base exception
✅ PASS: Exception substitution works correctly
✅ PASS: Exception context works
✅ PASS: WordPressAdapter implements interface
✅ PASS: Adapter has all required methods
✅ PASS: Adapter methods return correct types
⚠️  SKIP: WordPress not loaded
✅ PASS: Custom processor can be created
✅ PASS: Full flow works correctly
✅ PASS: Container clearing works
✅ PASS: Container set_instance works

=== Test Results ===
Passed: 17/18
✅ All tests passed!
```

### Option 2: WordPress Integration Tests

```bash
# In WordPress WP-CLI
wp eval "
    \$s1 = ImageOptimizer\Core\Container::get_priority_service();
    \$s2 = ImageOptimizer\Core\Container::get_priority_service();
    echo (\$s1 === \$s2) ? '✅ Caching works' : '❌ Caching failed';
"
```

### Option 3: Manual Browser Testing

1. Go to WordPress admin → Settings → Image Optimizer
2. Load a page with featured image in browser
3. Check page source (Ctrl+U):
   - Should see `<link rel="preload" ... />` in `<head>`
   - Should see no JavaScript errors in console
   - LCP image should preload early

---

## Pre-Deployment Checklist

Before merging/deploying:

- [ ] Unit tests pass: `php tests/test-solid-refactoring.php`
- [ ] No direct service instantiation: `grep -n "new.*Service()" odr-image-optimizer.php`
- [ ] All Container getters exist: `grep -c "get_.*_service" includes/core/class-container.php` (should be ≥4)
- [ ] Exception hierarchy valid: All exceptions extend `ImageOptimizerException`
- [ ] WordPressAdapter complete: 9 methods in interface
- [ ] README updated: Mentions all 5 SOLID principles with status
- [ ] EXTENDING.md created: Includes AVIF processor example
- [ ] No syntax errors: `php -l includes/**/*.php`
- [ ] Composer autoloader updated: `composer dump-autoload`
- [ ] Test with running WordPress: Load page, verify preload in HTML

---

## Common Issues & Fixes

### Issue: Container tests fail
**Solution:** Ensure Container::clear() is called between tests
```php
\ImageOptimizer\Core\Container::clear();
```

### Issue: PriorityService state test fails
**Solution:** Use reflection to access private property
```php
$ref = new \ReflectionClass('ImageOptimizer\Services\PriorityService');
$prop = $ref->getProperty('lcp_id');
$prop->setAccessible(true);
```

### Issue: WordPress adapter methods return null
**Solution:** Tests only work in WordPress context; use grep to verify code

### Issue: Static state still exists
**Solution:** Search for `static function` or `static $` in PriorityService
```bash
grep -n "static" includes/Services/class-priority-service.php
# Should return 0 matches
```

---

## Test Coverage Matrix

| Principle | Test Category | Status |
|-----------|---|---|
| **SRP** | Instance State Tests | ✅ Complete |
| **OCP** | Processor Registry Tests | ✅ Complete |
| **LSP** | Exception Hierarchy Tests | ✅ Complete |
| **ISP** | WordPress Adapter Tests | ✅ Complete |
| **DIP** | DI Container Tests | ✅ Complete |
| **Integration** | Full Flow Tests | ✅ Complete |

---

## Next Steps

1. Run tests: `php tests/test-solid-refactoring.php`
2. Fix any failures
3. Run WordPress integration tests
4. Test in browser with real featured image
5. Verify Lighthouse score remains 100/100
6. Merge to main branch
7. Deploy to production

---

**Test Suite Created:** February 22, 2026  
**Last Updated:** February 22, 2026
