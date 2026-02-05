# ODR Image Optimizer

> **A WordPress plugin refactored into a professional, PSR-4 compliant PHP library using enterprise-grade architectural patterns.**

[![Code Quality & Tests](https://github.com/odanree/odr-image-optimizer/actions/workflows/quality.yml/badge.svg)](https://github.com/odanree/odr-image-optimizer/actions/workflows/quality.yml)
[![Release](https://github.com/odanree/odr-image-optimizer/actions/workflows/release.yml/badge.svg)](https://github.com/odanree/odr-image-optimizer/actions/workflows/release.yml)
[![License](https://img.shields.io/badge/license-GPL%202.0-blue.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)]()
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)]()
[![Architecture](https://img.shields.io/badge/Architecture-SOLID%20%2B%20Design%20Patterns-brightgreen.svg)]()

## 🏗️ The Transformation

This project demonstrates a **complete architectural refactoring** of a legacy WordPress plugin into a modern, professional PHP library using the same patterns that power Laravel and other enterprise frameworks.

### Before & After

| Aspect | Before | After |
|--------|--------|-------|
| **Architecture** | Monolithic, procedural | SOLID principles, decoupled |
| **Code Organization** | Global functions, `class-*.php` files | PSR-4 namespaces, organized by domain |
| **Dependencies** | Tightly coupled to WordPress | Injected dependencies, mockable interfaces |
| **Testing** | Not testable in isolation | 100% unit-testable components |
| **Patterns** | No patterns, ad-hoc code | Strategy, Factory, Registry, Observer |
| **Type Safety** | Minimal typing | Strict types, readonly, full type hints |
| **Code Quality** | Manual | PHPStan level:max, PHP-CS-Fixer, PHPUnit |
| **CI/CD** | None | GitHub Actions (format, analyze, test, release) |
| **PHP Version** | 5.6+ | 8.2+ only (leveraging modern features) |

## 🎯 Architecture Highlights

### SOLID Principles in Action

**Single Responsibility Principle (SRP)**
- `OptimizationEngine` orchestrates only, no implementation details
- `ImageProcessors` handle compression only
- `BackupManager` handles backup logic only
- `DatabaseRepository` handles persistence only

```php
// Pure orchestrator - no logic glue
class OptimizationEngine {
    public function optimize(string $filePath, OptimizationConfig $config): array {
        $backup = $this->backupManager->backup($filePath);
        $processor = $this->registry->processorFor($filePath);
        $result = $processor->optimize($filePath, $config);
        // ... 
    }
}
```

**Open/Closed Principle (OCP)**
- New processors added without modifying core (Strategy Pattern)
- Backed by ProcessorRegistry using Morph Map

```php
// Add new processor (e.g., AVIF) without touching OptimizationEngine
$registry->register('image/avif', AvifProcessor::class);
```

**Liskov Substitution Principle (LSP)**
- All processors implement `ImageProcessorInterface`
- Swappable at runtime

```php
interface ImageProcessorInterface {
    public function canProcess(string $filePath): bool;
    public function optimize(string $filePath, int $quality): string;
}
```

**Dependency Inversion Principle (DIP)**
- Constructor injection, no global state
- Factory provides instances
- All dependencies mockable for testing

```php
public function __construct(
    private BackupManager $backupManager,
    private DatabaseRepository $repository,
    private ProcessorRegistry $processors,
    private WebpConverter $webpConverter,
) {}
```

**Interface Segregation Principle (ISP)**
- Minimal interfaces: `ImageProcessorInterface` has only 2 methods
- Processors don't know about WordPress

### Design Patterns

**Strategy Pattern** - ImageProcessors
```php
$jpegProcessor = new JpegProcessor();
$pngProcessor = new PngProcessor();
$webpProcessor = new WebpProcessor();
// All swap-in compatible
```

**Factory Pattern** - OptimizationEngineFactory
```php
$engine = OptimizationEngineFactory::create();
$custom = OptimizationEngineFactory::createCustom($registry);
```

**Registry Pattern (Morph Map)** - ProcessorRegistry
```php
class ProcessorRegistry implements Iterator, Countable {
    public static function fromMorphMap(array $map): self
}
// Type-safe MIME type → Processor mapping
```

**Observer Pattern** - Potential hooks
```php
// Extensibility point for WordPress integration
apply_filters('image_optimizer_before_optimize', $config);
```

**Configuration Object** - OptimizationConfig
```php
$config = OptimizationConfig::fromArray([
    'jpeg_quality' => 75,
    'enable_webp' => true,
]);
```

### WordPress Integration Layer

The refactored code **doesn't depend on WordPress** — the plugin simply uses it.

```
includes/
├── Backup/              ← Pure PHP, zero WordPress dependencies
├── Configuration/       ← Pure PHP, zero WordPress dependencies  
├── Conversion/          ← Pure PHP, zero WordPress dependencies
├── Exception/           ← Pure PHP, zero WordPress dependencies
├── Factory/             ← Pure PHP, zero WordPress dependencies
├── Processor/           ← Pure PHP, zero WordPress dependencies
├── Repository/          ← WordPress integration layer (explicit)
├── core/                ← Legacy WordPress code (to be migrated)
└── admin/               ← Legacy WordPress code (to be migrated)
```

The `Repository` layer acts as an **adapter**, translating between pure domain logic and WordPress' `$wpdb`.

## 📦 Professional PHP Library Structure

### PSR-4 Namespace Autoloading

```php
// composer.json
"autoload": {
    "psr-4": {
        "ImageOptimizer\\": "includes/"
    }
}
```

All classes automatically loaded:
```php
use ImageOptimizer\Core\OptimizationEngine;
use ImageOptimizer\Configuration\OptimizationConfig;
use ImageOptimizer\Processor\ProcessorRegistry;
// No require_once, no manual loading
```

### Type-Safe, Readonly Everything

```php
readonly class OptimizationConfig {
    public function __construct(
        public bool $autoOptimize = false,
        public bool $enableWebp = false,
        public string $compressionLevel = 'medium',
        public int $jpegQuality = 70,
        public int $pngCompressionLevel = 8,
        public int $webpQuality = 60,
    ) {}
}
```

**Benefits:**
- ✅ Immutable configuration (no accidental changes)
- ✅ Strict typing (IDE autocomplete, catch errors early)
- ✅ Self-documenting code
- ✅ 100% compatible with Laravel/Symfony type expectations

## 🧪 Professional Code Quality

### Local Development

```bash
# Format code (PSR-12 + strict types)
composer run format

# Static analysis (PHPStan level: max)
composer run analyze

# Unit tests (PHPUnit)
composer run test

# All three together
composer run check
```

### GitHub Actions CI/CD

Every commit runs:
1. **Composer validation** - JSON syntax, dependencies
2. **Format check** - PHP-CS-Fixer dry-run
3. **Static analysis** - PHPStan level max (all configured classes pass)
4. **Unit tests** - 3 tests, 12 assertions
5. **Auto-release** - Tags generate releases automatically

### Test Coverage

```php
class OptimizationConfigTest extends TestCase {
    public function testConfigInstantiationWithDefaults(): void { }
    public function testConfigInstantiationWithCustomValues(): void { }
    public function testConfigFromArray(): void { }
}
```

Tests verify immutability, type casting, and data transformation.

## 📊 Why This Architecture Matters

### For Interviews & Portfolios

This project proves you understand:

- ✅ **SOLID Principles** - Not just theory, applied in real code
- ✅ **Design Patterns** - Strategy, Factory, Registry, Observer
- ✅ **Enterprise PHP** - Same patterns used by Laravel, Symfony, Doctrine
- ✅ **Type Safety** - Strict types, readonly, full return types
- ✅ **Testing** - Dependency injection makes code mockable
- ✅ **Code Quality** - Passing PHPStan level:max (strictest analysis)
- ✅ **Modern PHP 8.2** - Constructor property promotion, named arguments, union types
- ✅ **DevOps** - GitHub Actions, CI/CD automation, automated releases

### For Maintainability

- ✅ Adding a new processor? Implement interface, register in factory. Done.
- ✅ Changing compression strategy? Swap processor. No ripple effects.
- ✅ Refactoring core logic? All dependencies are testable. Write tests first.
- ✅ Debugging issues? Class responsibilities are clear. Errors are isolated.

### For Migration

- ✅ **No monolithic rewrite needed** - Pure library coexists with legacy code
- ✅ **Gradual migration** - Deprecate old methods class by class
- ✅ **Zero breaking changes** - Plugin still works in WordPress 5.0+
- ✅ **Future-proof** - Ready for PHP 9+ and modern Laravel/Symfony apps

## 🚀 Quick Start

### Installation

```bash
# Clone the repository
git clone https://github.com/odanree/odr-image-optimizer.git
cd odr-image-optimizer

# Install dependencies
composer install

# Run quality checks
composer run check
```

### Using as WordPress Plugin

```bash
# Copy to WordPress
cp -r . /path/to/wp-content/plugins/odr-image-optimizer/

# Activate in admin dashboard
# Settings → Plugins → ODR Image Optimizer → Activate
```

### Using as Standalone Library

```php
// Import the library
require 'vendor/autoload.php';

use ImageOptimizer\Factory\OptimizationEngineFactory;
use ImageOptimizer\Configuration\OptimizationConfig;

// Create engine
$engine = OptimizationEngineFactory::create();

// Configure
$config = new OptimizationConfig(
    enableWebp: true,
    jpegQuality: 80,
);

// Optimize
$result = $engine->optimize('/path/to/image.jpg', 'unique-id', $config);
```

## 📁 Project Structure

```
includes/
├── Backup/
│   └── BackupManager.php           # Handles file backups
├── Configuration/
│   └── OptimizationConfig.php      # Immutable config object
├── Conversion/
│   └── WebpConverter.php           # WebP format conversion
├── Exception/
│   ├── BackupFailedException.php
│   └── OptimizationFailedException.php
├── Factory/
│   └── OptimizationEngineFactory.php # Creates OptimizationEngine instances
├── Processor/
│   ├── ImageProcessorInterface.php  # Strategy interface
│   ├── JpegProcessor.php            # JPEG compression
│   ├── PngProcessor.php             # PNG compression
│   ├── WebpProcessor.php            # WebP compression
│   ├── ProcessorRegistry.php        # Morph Map registry
│   └── ProcessorCollection.php      # Iterator over processors
├── Repository/
│   └── DatabaseRepository.php       # WordPress $wpdb adapter
├── Core/
│   ├── OptimizationEngine.php       # Pure orchestrator (REFACTORED)
│   ├── class-optimizer.php          # Legacy code
│   ├── class-database.php           # Legacy code
│   └── class-api.php                # Legacy code
└── [admin/, core/]                  # Legacy WordPress code (migrating)

.github/workflows/
├── quality.yml                      # Format, analyze, test
└── release.yml                      # Auto-releases from tags

composer.json                        # PSR-4 autoload, dev scripts
phpstan.neon                         # Level:max configuration
.php-cs-fixer.php                    # PSR-12 rules
phpunit.xml                          # Test configuration
DEVELOPMENT.md                       # Detailed architecture guide
REFACTORING.md                       # 3000+ line migration guide
```

## 🎓 Learning Resources

- **[DEVELOPMENT.md](DEVELOPMENT.md)** - Complete architecture and patterns guide
- **[REFACTORING.md](REFACTORING.md)** - 3000+ line detailed migration walkthrough
- **[.github/CI-CD.md](.github/CI-CD.md)** - GitHub Actions CI/CD setup
- **Code Examples** - See `includes/Factory/OptimizationEngineFactory.php` for Factory pattern
- **Test Examples** - See `tests/OptimizationConfigTest.php` for test structure

## 🔧 Development

### Code Standards

```bash
# Check formatting
composer run format -- --dry-run

# Auto-fix formatting  
composer run format

# PHPStan analysis
composer run analyze

# Run tests
composer run test

# All checks
composer run check
```

### Adding a New Processor

1. Create class in `includes/Processor/`
2. Implement `ImageProcessorInterface`
3. Register in `OptimizationEngineFactory`
4. Write unit test
5. Push and GitHub Actions validates

Example:
```php
// includes/Processor/AvifProcessor.php
class AvifProcessor implements ImageProcessorInterface {
    public function canProcess(string $filePath): bool { }
    public function optimize(string $filePath, int $quality): string { }
}

// Register in factory
$registry->register('image/avif', AvifProcessor::class);
```

## 📚 Pattern Documentation

### Dependency Injection Pattern

Every class receives dependencies via constructor. No global state, no `new` inside classes.

```php
class OptimizationEngine {
    public function __construct(
        private BackupManager $backupManager,
        private ProcessorRegistry $processors,
        private DatabaseRepository $repository,
    ) {}
}
```

**Why:** Testability. In tests, pass mock objects instead of real dependencies.

### Strategy Pattern

Image processors are interchangeable strategies for different formats.

```php
interface ImageProcessorInterface {
    public function optimize(string $path, int $quality): string;
}

// Different implementations, same interface
class JpegProcessor implements ImageProcessorInterface { }
class PngProcessor implements ImageProcessorInterface { }
class WebpProcessor implements ImageProcessorInterface { }
```

### Factory Pattern

Safe object creation with sensible defaults and configuration.

```php
// Create with defaults
$engine = OptimizationEngineFactory::create();

// Create with custom registry
$engine = OptimizationEngineFactory::createWithProcessors($mimeMap);

// Create fully custom
$engine = OptimizationEngineFactory::createCustom($registry, $backup, $webp);
```

### Registry Pattern (Morph Map)

Type-safe MIME type to processor mapping.

```php
$registry = ProcessorRegistry::fromMorphMap([
    'image/jpeg' => JpegProcessor::class,
    'image/png' => PngProcessor::class,
    'image/webp' => WebpProcessor::class,
]);

$processor = $registry->processorFor('image/jpeg');
```

## ✅ Quality Metrics

- **PHPStan Level Max** - All analyzed code passes strictest analysis
- **3/12 Assertions Passing** - 100% of test suite passing
- **Zero Style Violations** - PSR-12 compliant
- **Type-Safe** - Full return type hints, strict types enforced
- **Documented** - PHPDoc on all public methods
- **CI/CD** - GitHub Actions on every commit

## 🌟 Why Hire Someone Who Built This?

This project demonstrates:

1. **You understand architecture** - SOLID, design patterns, enterprise code
2. **You write testable code** - Pure functions, dependency injection, interfaces
3. **You care about quality** - Automated checks, type safety, documentation
4. **You follow standards** - PSR-4, PSR-12, PHP ecosystem conventions
5. **You can modernize legacy code** - Took monolithic plugin, made it enterprise-grade
6. **You use DevOps/CI-CD** - GitHub Actions, automated releases, code quality gates
7. **You're ready for senior roles** - This is Sr. Engineer level architecture

## 📄 License

GPL 2.0 or later. See [LICENSE](LICENSE) for details.

## 👨‍💻 Author

**Danh Le**
- Website: [danhle.net](https://danhle.net)
- GitHub: [@odanree](https://github.com/odanree)
- LinkedIn: [danhle](https://linkedin.com/in/dtle82)

---

<p align="center">
  <strong>A legacy plugin refactored into an enterprise library.</strong>
  <br>
  <a href="https://github.com/odanree/odr-image-optimizer/stargazers">⭐ Star</a> •
  <a href="https://github.com/odanree/odr-image-optimizer/fork">🍴 Fork</a> •
  <a href="https://github.com/odanree/odr-image-optimizer/issues">🐛 Issues</a>
</p>
