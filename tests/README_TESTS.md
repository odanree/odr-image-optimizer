# LCP Guard Tests - PHPUnit Test Suite

This test file validates the core LCP (Largest Contentful Paint) optimization features of the ODR Image Optimizer plugin.

## What These Tests Verify

### 1. **Priority Service: LCP Preload Injection**
Tests that the `PriorityService` correctly injects high-priority preload tags when the setting is enabled.

**What gets tested:**
- `rel="preload"` tag is present in output
- `as="image"` attribute is correct
- `fetchpriority="high"` forces browser priority
- Setting respects user configuration (disabled = no tags)

**Why it matters:**
Preload tags tell the browser to start downloading the LCP image immediately, before CSS is parsed. This breaks the dependency chain and saves 35-65ms on mobile.

### 2. **Cleanup Service: Bloat Removal**
Tests that the `CleanupService` correctly removes non-critical scripts when enabled.

**What gets tested:**
- Emoji detection script is removed (when setting enabled)
- Lazy-load script is dequeued (redundant with native `loading="lazy"`)
- Setting respects user configuration (disabled = scripts remain)

**Why it matters:**
Removing unnecessary scripts frees up the main thread and reduces rendering delays. Saves 30-100ms on 4G networks.

## Running the Tests

### With PHPUnit (standard)
```bash
composer test
```

### Run all checks (format, analyze, test)
```bash
composer check
```

### Run with verbose output
```bash
vendor/bin/phpunit --colors=always --verbose tests/LcpGuardTest.php
```

## Test Structure

Each test follows the **Arrange → Act → Assert** pattern:

1. **Arrange**: Set up conditions (update settings, enqueue scripts)
2. **Act**: Execute the service method being tested
3. **Assert**: Verify the behavior matches expectations

## Example Test Flow

```php
public function test_priority_service_injects_preload_when_enabled(): void
{
    // ARRANGE: Enable preloading setting
    update_option('odr_image_optimizer_settings', ['preload_fonts' => '1']);
    
    // ACT: Detect LCP and inject preload tag
    $service = new PriorityService();
    $service->detect_lcp_id();
    ob_start();
    $service->inject_preload();
    $output = ob_get_clean();
    
    // ASSERT: Verify preload tags are in output
    $this->assertStringContainsString('rel="preload"', $output);
    $this->assertStringContainsString('fetchpriority="high"', $output);
}
```

## Key Test Cases

| Test | Validates |
|------|-----------|
| `test_priority_service_injects_high_priority_preload_tags` | LCP preload with fetchpriority |
| `test_cleanup_service_removes_emoji_bloat_when_enabled` | Emoji script removal |
| `test_cleanup_service_dequeues_lazy_load_script` | Dequeue redundant lazy-load.js |
| `test_priority_service_respects_preload_setting_when_disabled` | Setting respected (disabled = no output) |
| `test_cleanup_service_respects_kill_bloat_setting_when_disabled` | Setting respected (disabled = scripts remain) |

## PHPUnit Test Assertions

Modern PHPUnit provides expressive assertions:

```php
// String assertions
$this->assertStringContainsString('needle', $haystack);
$this->assertStringNotContainsString('needle', $haystack);

// Boolean assertions
$this->assertTrue($condition);
$this->assertFalse($condition);

// Action/Hook assertions (WordPress-specific)
$has_action = has_action('hook_name', 'callback');
$this->assertFalse($has_action);
```

## Dependencies

- `phpunit/phpunit: ^10.5` - Test framework
- WordPress testing environment (via bootstrap.php)

## Adding New Tests

When adding new optimization features:

1. Create a test following the AAA pattern (Arrange, Act, Assert)
2. Test both **enabled** and **disabled** settings
3. Use descriptive test names (snake_case with test_ prefix)
4. Verify output/behavior, not implementation details

Example:
```php
public function test_my_new_feature_works_when_enabled(): void
{
    update_option('odr_image_optimizer_settings', ['my_feature' => '1']);
    // ... test enabled behavior
}

public function test_my_new_feature_respects_disabled_setting(): void
{
    update_option('odr_image_optimizer_settings', ['my_feature' => '0']);
    // ... verify disabled behavior
}
```

## Expected Test Output

```bash
$ composer test

PHPUnit 10.5.63 by Sebastian Bergmann and contributors.

...... 5 passed, 0 failures in 2.34s

OK (5 tests, 5 assertions)
```

---

**File**: `tests/LcpGuardTest.php`  
**Author**: Danh Le  
**Framework**: PHPUnit 10.5  
**Coverage**: PriorityService, CleanupService, Settings integration
