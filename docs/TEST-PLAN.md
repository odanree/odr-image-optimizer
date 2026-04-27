# Testing Plan for SOLID Refactoring

Complete testing checklist to verify all refactoring changes work correctly.

## Quick Verification

Run this to verify all changes:

```bash
cd /Users/odanree/Documents/Projects/wordpress-local/wp-content/plugins/odr-image-optimizer
php tests/verify-changes.php
```

**Expected Output:**
```
✅ All refactoring changes verified!
```

---

## What to Test

### 1. **Dependency Injection Container** ✅

**Goal:** Verify services are managed by Container, not instantiated directly

**Tests:**

1. **Service Caching**
   ```php
   $s1 = \ImageOptimizer\Core\Container::get_priority_service();
   $s2 = \ImageOptimizer\Core\Container::get_priority_service();
   assert($s1 === $s2, 'Should return same instance');
   ```

2. **All Services Cached**
   ```php
   $p = \ImageOptimizer\Core\Container::get_priority_service();
   $a = \ImageOptimizer\Core\Container::get_asset_manager();
   $c = \ImageOptimizer\Core\Container::get_cleanup_service();
   $w = \ImageOptimizer\Core\Container::get_wordpress_adapter();
   // Each should be cached on subsequent calls
   ```

3. **Container Clear for Testing**
   ```php
   $s1 = \ImageOptimizer\Core\Container::get_priority_service();
   \ImageOptimizer\Core\Container::clear();
   $s2 = \ImageOptimizer\Core\Container::get_priority_service();
   assert($s1 !== $s2, 'Should create new instance after clear');
   ```

**Verification:** ✅ `verify-changes.php` confirms Container has all 4 getters

---

### 2. **Instance State (Not Static)** ✅

**Goal:** Verify PriorityService uses instance variables instead of static state

**Tests:**

1. **No Static Variables**
   ```bash
   grep 'private static' includes/Services/class-priority-service.php
   # Should return 0 results ✅
   ```

2. **Instance State Isolation**
   ```php
   $s1 = new \ImageOptimizer\Services\PriorityService();
   $s2 = new \ImageOptimizer\Services\PriorityService();
   
   // Each has independent state
   $ref = new \ReflectionClass($s1);
   $prop = $ref->getProperty('lcp_id');
   $prop->setAccessible(true);
   
   $prop->setValue($s1, 123);
   assert($prop->getValue($s1) === 123);
   assert($prop->getValue($s2) === null);
   ```

**Verification:** ✅ `verify-changes.php` confirms no `private static` in PriorityService

---

### 3. **WordPress Integration Adapter** ✅

**Goal:** Verify adapter interface and implementation for testability

**Tests:**

1. **Adapter Implements Interface**
   ```php
   $adapter = new \ImageOptimizer\Adapter\WordPressAdapter();
   assert($adapter instanceof \ImageOptimizer\Adapter\WordPressAdapterInterface);
   ```

2. **Has All Methods**
   ```php
   $methods = [
       'is_singular', 'is_admin', 'is_frontend',
       'get_post_thumbnail_id', 'get_attachment_image_url',
       'get_attachment_image_srcset', 'dequeue_script',
       'remove_action', 'get_option'
   ];
   foreach ($methods as $m) {
       assert(method_exists($adapter, $m));
   }
   ```

3. **Method Return Types**
   ```php
   assert(is_bool($adapter->is_singular()));
   assert(is_bool($adapter->is_admin()));
   assert(is_bool($adapter->is_frontend()));
   ```

**Verification:** ✅ `verify-changes.php` confirms interface and implementation

---

### 4. **Exception Hierarchy** ✅

**Goal:** Verify Liskov Substitution with exception base class

**Tests:**

1. **All Extend Base**
   ```php
   assert(new \ImageOptimizer\Exception\OptimizationFailedException() 
       instanceof \ImageOptimizer\Exception\ImageOptimizerException);
   assert(new \ImageOptimizer\Exception\BackupFailedException() 
       instanceof \ImageOptimizer\Exception\ImageOptimizerException);
   assert(new \ImageOptimizer\Exception\ProcessorNotAvailableException('dep', 'msg') 
       instanceof \ImageOptimizer\Exception\ImageOptimizerException);
   ```

2. **Substitutability**
   ```php
   $exceptions = [
       new \ImageOptimizer\Exception\OptimizationFailedException(),
       new \ImageOptimizer\Exception\BackupFailedException(),
       new \ImageOptimizer\Exception\ProcessorNotAvailableException('x', 'y'),
   ];
   
   foreach ($exceptions as $e) {
       try {
           throw $e;
       } catch (\ImageOptimizer\Exception\ImageOptimizerException $base) {
           // All caught as base type ✅
       }
   }
   ```

3. **Exception Context**
   ```php
   $ex = new \ImageOptimizer\Exception\OptimizationFailedException(
       'Test',
       0,
       null,
       ['file' => 'test.jpg']
   );
   assert($ex->get_context()['file'] === 'test.jpg');
   ```

**Verification:** ✅ `verify-changes.php` confirms exception hierarchy

---

### 5. **No Direct Service Instantiation** ✅

**Goal:** Verify plugin uses Container instead of `new Service()`

**Tests:**

1. **Frontend Hooks Use Container**
   ```bash
   grep -c 'Container::get_' odr-image-optimizer.php
   # Should be ≥ 5 matches ✅
   ```

2. **No Direct Instantiation of Frontend Services**
   ```bash
   grep 'new .*PriorityService\|new .*AssetManager\|new .*CleanupService' odr-image-optimizer.php
   # Should return 0 matches ✅
   ```

**Verification:** ✅ `verify-changes.php` confirms no direct instantiation

---

### 6. **PHP Syntax Valid** ✅

**Goal:** Verify no syntax errors in all modified files

```bash
php -l includes/core/class-container.php
php -l includes/Services/class-priority-service.php
php -l includes/Adapter/WordPressAdapterInterface.php
php -l includes/Adapter/WordPressAdapter.php
php -l includes/Exception/ImageOptimizerException.php
php -l includes/Exception/OptimizationFailedException.php
php -l includes/Exception/BackupFailedException.php
php -l includes/Exception/ProcessorNotAvailableException.php
# All should return: "No syntax errors detected" ✅
```

**Verification:** ✅ `verify-changes.php` confirms all syntax valid

---

### 7. **Documentation Complete** ✅

**Goal:** Verify documentation updated with SOLID principles

**Tests:**

1. **README Includes All Principles**
   ```bash
   grep -c 'Single Responsibility\|Open/Closed\|Liskov Substitution\|Interface Segregation\|Dependency Inversion' README.md
   # Should find all 5 ✅
   ```

2. **EXTENDING.md Exists and Complete**
   ```bash
   grep -c 'Adding Custom Image Processors\|AvifProcessor\|ProcessorRegistry' docs/EXTENDING.md
   # Should find examples ✅
   ```

**Verification:** ✅ `verify-changes.php` confirms documentation complete

---

## Local wp-env Integration Testing (WooCommerce code paths)

Use this methodology for bugs that require WooCommerce context — e.g. order emails, checkout, product image rendering. Gives an isolated, reproducible environment without touching prod.

### Setup (one-time)

Requires `vps-woocommerce-stack` repo checked out alongside this one. The stack's `.wp-env.json` maps `../odr-image-optimizer` as a local plugin.

```bash
cd ../vps-woocommerce-stack

# Windows: check excluded ports first
netsh interface ipv4 show excludedportrange protocol=tcp

export WP_ENV_PORT=9080
export WP_ENV_TESTS_PORT=9091
npx wp-env start
```

Admin: `http://localhost:9080/wp-admin` — user `admin` / pass `password`

Fix memory limit if needed (ODR image conversion requires > 40M default):
```bash
docker exec [wordpress-container-id] //bin/sh -c \
  "sed -i \"s/define( 'WP_MEMORY_LIMIT', 256M )/define( 'WP_MEMORY_LIMIT', '256M' )/\" //var/www/html/wp-config.php"
# Or: npx wp-env run cli wp config set WP_MEMORY_LIMIT '256M'
```

### Test procedure for WooCommerce image path bugs

1. **Create a product with a JPEG image**
   - Upload any JPEG via `/wp-admin/media-new.php`
   - Assign as product image, or use docker cp + eval-file to attach programmatically

2. **Optimize the image**
   - Go to `/wp-admin/admin.php?page=image-optimizer`
   - Optimize the product image — this creates the `.webp` file
   - **Critical:** without the `.webp`, the plugin exits early and the bug path is never hit

3. **Enable Cheque payment**
   ```bash
   npx wp-env run cli wp wc payment_gateway update cheque --enabled=true --user=1
   ```

4. **Place a test order**
   - Add the product to cart → checkout → pay with Cheque
   - Cheque calls `payment_complete()` immediately, triggering the WC order email and the `wp_get_attachment_image()` call with array `$size`

5. **Check the debug log**
   ```bash
   npx wp-env run cli wp eval "echo file_exists(WP_CONTENT_DIR . '/debug.log') ? file_get_contents(WP_CONTENT_DIR . '/debug.log') : 'no debug.log';"
   ```
   - No debug.log or empty log = no PHP errors = fix confirmed

### When to use local vs prod smoke test

| Scenario | Use |
|---|---|
| Pre-merge fix verification | Local wp-env |
| Bug requires specific WC state (order emails, checkout) | Local wp-env |
| Post-merge smoke test with real data | Prod (with snapshot taken first) |
| Bug only surfaces with real uploaded images | Prod |

---

## Integration Testing (Requires WordPress)

### Test 1: Frontend Page Load

1. Start WordPress server:
   ```bash
   cd /Users/odanree/Documents/Projects/wordpress-local
   ./start-server-network.sh
   ```

2. Navigate to a post/page with featured image

3. Check page source for preload link:
   ```bash
   curl -s http://localhost:8080/test-post/ | grep 'rel="preload"'
   # Should find: <link rel="preload" as="image" href="..." />
   ```

4. Check browser console: No JavaScript errors ✅

### Test 2: Verify Container Works in WordPress

In WP-CLI:

```bash
wp eval '
$s1 = \ImageOptimizer\Core\Container::get_priority_service();
$s2 = \ImageOptimizer\Core\Container::get_priority_service();
echo ($s1 === $s2) ? "✅ Container caching works" : "❌ Failed";
'
```

### Test 3: Verify Service State

In WP-CLI:

```bash
wp eval '
$service = new \ImageOptimizer\Services\PriorityService();
$ref = new \ReflectionClass($service);
$prop = $ref->getProperty("lcp_id");
$prop->setAccessible(true);
$prop->setValue($service, 999);
echo $prop->getValue($service) === 999 ? "✅ Instance state works" : "❌ Failed";
'
```

### Test 4: Lighthouse Score

1. Run Lighthouse audit on frontpage:
   ```bash
   lighthouse http://localhost:8080 --view
   ```

2. Verify Performance score remains 100/100 ✅

### Test 5: Exception Handling

In WP-CLI:

```bash
wp eval '
try {
    throw new \ImageOptimizer\Exception\OptimizationFailedException("Test");
} catch (\ImageOptimizer\Exception\ImageOptimizerException $e) {
    echo "✅ Exception hierarchy works";
}
'
```

---

## Pre-Deployment Checklist

Before merging PR:

- [ ] Run `php verify-changes.php` - all checks pass
- [ ] No direct service instantiation: `grep -c 'new.*Service(' odr-image-optimizer.php` returns 0
- [ ] All syntax valid: `php -l` on all modified files
- [ ] Container has 4 getters: 4+ matches for `get_.*_service`
- [ ] Exception hierarchy: All extend `ImageOptimizerException`
- [ ] WordPressAdapter complete: 9 methods defined
- [ ] README updated: All 5 SOLID principles documented
- [ ] EXTENDING.md created: With AVIF processor example
- [ ] Autoloader updated: `composer dump-autoload`
- [ ] Lighthouse score: Still 100/100
- [ ] No PHP errors on page load
- [ ] Container clear/set_instance work

---

## Testing Commands Summary

```bash
# Quick verification
cd /Users/odanree/Documents/Projects/wordpress-local/wp-content/plugins/odr-image-optimizer
php verify-changes.php

# Check container usage
grep 'Container::get_' odr-image-optimizer.php | wc -l  # Should be ≥ 5

# Check no direct instantiation
grep -E 'new \\(ImageOptimizer\\Services' odr-image-optimizer.php | wc -l  # Should be 0

# Check exception hierarchy
grep -E 'extends ImageOptimizerException' includes/Exception/*.php | wc -l  # Should be 3

# Check syntax
php -l includes/core/class-container.php
php -l includes/Services/class-priority-service.php
php -l includes/Adapter/WordPressAdapterInterface.php
php -l includes/Adapter/WordPressAdapter.php

# Check adapter methods
grep 'public function' includes/Adapter/WordPressAdapterInterface.php | wc -l  # Should be 9

# Check SOLID docs
grep -E 'Principle|SOLID' README.md | wc -l  # Should be ≥ 15
```

---

## Troubleshooting

### Issue: `grep` patterns not matching
**Solution:** Use exact pattern from files

### Issue: Container not caching
**Solution:** Check that `set_instance()` is being used

### Issue: PHP syntax errors
**Solution:** Check file encoding (should be UTF-8 with no BOM)

### Issue: Lighthouse score dropped
**Solution:** Check that preload tag is still being injected correctly

---

## Test Results Template

```
SOLID Refactoring - Test Results
================================

Date: _____________
Tester: ___________

Container Tests:        ✅ / ❌
State Isolation:        ✅ / ❌
Exception Hierarchy:    ✅ / ❌
WordPress Adapter:      ✅ / ❌
No Direct Instantiation: ✅ / ❌
Syntax Valid:           ✅ / ❌
Documentation:          ✅ / ❌

Integration Tests (if applicable):
- Frontend page load:   ✅ / ❌
- Lighthouse score:     ✅ / ❌ (Score: ___)
- WP-CLI tests:         ✅ / ❌

Notes:
_________________________________
_________________________________

Overall Status: ✅ PASS / ❌ FAIL
```

---

**Last Updated:** February 22, 2026  
**Verification Tool:** `verify-changes.php`
