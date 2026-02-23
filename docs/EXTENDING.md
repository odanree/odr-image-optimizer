# Extending ODR Image Optimizer

This guide shows how to extend the plugin with custom processors, services, and integrations using SOLID principles.

## Adding Custom Image Processors

The plugin uses a **pluggable registry system** to allow adding new image formats without modifying core files. This follows the **Open/Closed Principle** (open for extension, closed for modification).

### Example: Adding AVIF Support

AVIF (AV1 Image Format) is the next-generation image format. Here's how to add support:

#### Step 1: Create a Custom Processor

```php
<?php
// your-plugin/AvifProcessor.php

namespace YourPlugin;

use ImageOptimizer\Processor\ImageProcessorInterface;
use ImageOptimizer\Exception\OptimizationFailedException;

class AvifProcessor implements ImageProcessorInterface
{
    public function process(string $filePath, int $quality): bool
    {
        if (! file_exists($filePath)) {
            throw new OptimizationFailedException("File not found: {$filePath}");
        }

        if (! extension_loaded('imagick')) {
            throw new OptimizationFailedException('ImageMagick extension required for AVIF');
        }

        try {
            $image = new \Imagick($filePath);
            $image->setImageFormat('avif');
            $image->setImageCompressionQuality($quality);
            $image->writeImage($filePath);
            return true;
        } catch (\Exception $e) {
            throw new OptimizationFailedException("AVIF conversion failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function supports(string $filePath): bool
    {
        return pathinfo($filePath, PATHINFO_EXTENSION) === 'jpg' ||
               pathinfo($filePath, PATHINFO_EXTENSION) === 'png';
    }

    public function getMimeType(): string
    {
        return 'image/avif';
    }
}
```

#### Step 2: Register with ODR Image Optimizer

Register your processor via WordPress filter:

```php
<?php
// your-plugin/plugin.php

add_filter('image_optimizer_processors', function($processors) {
    // $processors is the ProcessorRegistry instance
    $processors['avif'] = new \YourPlugin\AvifProcessor();
    return $processors;
});
```

**How it works:**
1. The `ProcessorRegistry` maintains a morph map of processor classes
2. The filter allows runtime registration of new processors
3. When optimizing, the plugin checks all registered processors (SRP: each processor has one job)
4. New formats can be added without modifying ODR Image Optimizer code (OCP)

### SOLID Design Benefits

**Open/Closed Principle (OCP):**
- ✅ Open for extension: Add new processors via filter
- ✅ Closed for modification: No changes to existing ODR code

**Liskov Substitution Principle (LSP):**
- ✅ Your `AvifProcessor` is substitutable for any other processor
- ✅ All must implement `ImageProcessorInterface`
- ✅ All throw `OptimizationFailedException` on failure

**Single Responsibility (SRP):**
- ✅ `AvifProcessor` only handles AVIF conversion
- ✅ ODR handles registration, priority, orchestration
- ✅ No processor implements multiple formats

## Creating Custom Services

Services encapsulate business logic and are managed by the DI Container. The `PriorityService` example shows the pattern:

```php
<?php
namespace ImageOptimizer\Services;

class CustomService
{
    public function __construct(
        private readonly \ImageOptimizer\Adapter\WordPressAdapter $wp,
    ) {}

    public function do_something(): void
    {
        if (! $this->wp->is_frontend()) {
            return;
        }

        // Your logic here, depending on $wp adapter
    }
}
```

**Key Pattern:**
1. Services depend on interfaces, not WordPress functions directly
2. The `WordPressAdapter` abstracts WordPress calls (enables testing)
3. Services are instantiated by the Container (dependency injection)
4. Services have a single responsibility

## Testing Custom Processors

The adapter pattern makes testing easy:

```php
<?php
// tests/AvifProcessorTest.php

class AvifProcessorTest extends TestCase
{
    public function test_avif_processor_optimizes_jpeg()
    {
        $processor = new AvifProcessor();
        
        // Create temp JPEG
        $temp = tempnam('/tmp', 'test');
        copy('fixtures/test.jpg', $temp);

        // Process
        $result = $processor->process($temp, 80);
        $this->assertTrue($result);

        // Verify file was modified
        $this->assertFileExists($temp);
        unlink($temp);
    }

    public function test_processor_supports_jpg_png()
    {
        $processor = new AvifProcessor();
        $this->assertTrue($processor->supports('image.jpg'));
        $this->assertTrue($processor->supports('image.png'));
        $this->assertFalse($processor->supports('image.gif'));
    }

    public function test_processor_throws_on_missing_imagick()
    {
        // Mock ImageMagick not available
        $processor = new AvifProcessor();
        
        $this->expectException(OptimizationFailedException::class);
        $processor->process('test.jpg', 80);
    }
}
```

## Dependency Inversion: Manual Injection

The Container manages service lifecycle. For advanced scenarios, you can inject custom instances:

```php
<?php
// your-plugin/setup.php

$custom_service = new CustomService(
    new \ImageOptimizer\Adapter\WordPressAdapter()
);

// Override default instance in Container
\ImageOptimizer\Core\Container::set_instance('custom_service', $custom_service);

// Later, retrieve it
$service = \ImageOptimizer\Core\Container::get_priority_service();
```

This pattern ensures:
- ✅ Dependency Inversion: Your code depends on interfaces, not implementation
- ✅ Testability: Mock the adapter in tests
- ✅ Extensibility: Swap implementations via Container

## Exception Hierarchy

The plugin provides a strict exception hierarchy for LSP compliance:

```
ImageOptimizerException (base)
├── OptimizationFailedException (processor errors)
├── BackupFailedException (backup operations)
└── ProcessorNotAvailableException (missing dependencies)
```

Use appropriate exceptions:

```php
<?php
// In custom processor
if (! extension_loaded('imagick')) {
    throw new ProcessorNotAvailableException(
        'imagick',
        'ImageMagick extension not available'
    );
}

if ($conversion_failed) {
    throw new OptimizationFailedException(
        "Conversion failed: {$error}",
        0,
        $previous_exception
    );
}
```

## Registry System Deep Dive

The `ProcessorRegistry` is the extensibility foundation:

```php
<?php
// includes/Processor/ProcessorRegistry.php

class ProcessorRegistry
{
    // Lazy-load processors from morph map (class names)
    public static function fromMorphMap(array $map): self
    {
        return new self($map);
    }

    // Check if processor supports file
    public function find_processor(string $filePath): ?ImageProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($filePath)) {
                return $processor;
            }
        }
        return null;
    }
}
```

**How to use the registry:**

```php
<?php
// Add processor at runtime
$registry = \ImageOptimizer\Core\Container::get_tool_registry();

// Find suitable processor
$processor = $registry->find_processor('image.jpg');
if ($processor) {
    $processor->process('image.jpg', 80);
}
```

## Best Practices

### 1. Always Implement ImageProcessorInterface

```php
class CustomProcessor implements ImageProcessorInterface {
    public function process(string $filePath, int $quality): bool { }
    public function supports(string $filePath): bool { }
    public function getMimeType(): string { }
}
```

### 2. Use the WordPressAdapter for WordPress Calls

```php
// ✅ Good: Depends on abstraction
private readonly WordPressAdapter $wp;

// ❌ Bad: Direct WordPress function call
if (is_singular()) { }
```

### 3. Throw Appropriate Exceptions

```php
// ✅ Good: Specific exception
throw new ProcessorNotAvailableException('imagick', 'ImageMagick required');

// ❌ Bad: Generic exception
throw new Exception('Something went wrong');
```

### 4. Document Dependencies in Docblock

```php
/**
 * Requires: imagick PHP extension, ImageMagick binary
 * Throws: ProcessorNotAvailableException if dependencies missing
 */
public function process(string $filePath, int $quality): bool { }
```

### 5. Test with Mock WordPress Adapter

```php
// Create mock adapter for testing
$wp = $this->createMock(WordPressAdapterInterface::class);
$wp->method('is_frontend')->willReturn(true);

// Inject into service
$service = new CustomService($wp);
```

## See Also

- [ARCHITECTURE.md](ARCHITECTURE.md) - Full architecture reference
- [CASE_STUDY.md](../CASE_STUDY.md) - Performance optimization patterns
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow
