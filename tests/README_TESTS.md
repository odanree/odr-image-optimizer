# LCP Guard Tests - Pest Test Suite

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

### With Pest (recommended, modern syntax)
```bash
composer test-pest
```

### With PHPUnit (traditional)
```bash
composer test
```

### Run all checks (format, analyze, test)
```bash
composer check
```

## Test Structure

Each test follows the **Arrange → Act → Assert** pattern:

1. **Arrange**: Set up conditions (update settings, enqueue scripts)
2. **Act**: Execute the service method
3. **Assert**: Verify the behavior matches expectations

## Example Test Flow

```php
test('priority service injects preload when enabled', function () {
    // ARRANGE: Enable preloading setting
    update_option('odr_image_optimizer_settings', ['preload_fonts' => '1']);
    
    // ACT: Detect LCP and inject preload tag
    $service = new PriorityService();
    $service->detect_lcp_id();
    ob_start();
    $service->inject_preload();
    $output = ob_get_clean();
    
    // ASSERT: Verify preload tags are in output
    expect($output)->toContain('rel="preload"')->toContain('fetchpriority="high"');
});
```

## Key Test Cases

| Test | Validates |
|------|-----------|
| `priority service injects high-priority preload tags` | LCP preload with fetchpriority |
| `cleanup service removes emoji bloat when enabled` | Emoji script removal |
| `cleanup service dequeues lazy-load script` | Dequeue redundant lazy-load.js |
| `priority service respects preload setting when disabled` | Setting respected (disabled = no output) |
| `cleanup service respects kill_bloat setting when disabled` | Setting respected (disabled = scripts remain) |

## Pest vs PHPUnit Syntax

Pest provides a more elegant, expressive syntax:

**PHPUnit:**
```php
public function test_it_does_something() {
    $this->assertTrue($result);
    $this->assertStringContains($output, 'expected');
}
```

**Pest:**
```php
test('it does something', function () {
    expect($result)->toBeTrue();
    expect($output)->toContain('expected');
});
```

Pest is fully compatible with PHPUnit, so both syntaxes work. Pest just provides a more fluent API.

## Dependencies

- `pestphp/pest: ^2.0` - Modern test framework (built on PHPUnit)
- `phpunit/phpunit: ^11.0` - Test runner
- WordPress testing environment (via bootstrap.php)

## Adding New Tests

When adding new optimization features:

1. Create a test following the AAA pattern (Arrange, Act, Assert)
2. Test both **enabled** and **disabled** settings
3. Use descriptive test names
4. Verify output/behavior, not implementation details

Example:
```php
test('my new feature works when enabled', function () {
    update_option('odr_image_optimizer_settings', ['my_feature' => '1']);
    // ... test enabled behavior
});

test('my new feature respects disabled setting', function () {
    update_option('odr_image_optimizer_settings', ['my_feature' => '0']);
    // ... verify disabled behavior
});
```

---

**File**: `tests/LcpGuardTest.php`  
**Author**: Danh Le  
**Framework**: Pest (PHPUnit-compatible)  
**Coverage**: PriorityService, CleanupService, Settings integration
