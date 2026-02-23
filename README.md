# ODR Image Optimizer

> **The High-Performance Image Engine for Modern WordPress.**

![Lighthouse 100](https://img.shields.io/badge/Lighthouse-100-brightgreen?style=for-the-badge&logo=googlechrome)
![PHP 8.1+](https://img.shields.io/badge/PHP-8.1+-777bb4?style=for-the-badge&logo=php)
![License GPL 2.0](https://img.shields.io/badge/license-GPL%202.0-blue.svg?style=for-the-badge)

ODR Image Optimizer is a **SOLID-compliant** performance suite designed to reclaim the critical rendering path. By decoupling image processing from delivery policy, it achieves a **1.8s LCP** on throttled mobile connections with **100/100 Lighthouse Performance**.

## ⚡ Performance Benchmarks

Tested on a standard WordPress 6.9.1 installation using Lighthouse 13.0.1 (Mobile/Slow 4G) with on-demand navigation script loading.

- **Largest Contentful Paint (LCP):** 1.8s (run-to-run variance: 1.8s-2.0s)
- **First Contentful Paint (FCP):** 0.8s
- **Total Blocking Time (TBT):** 0ms
- **Cumulative Layout Shift (CLS):** 0
- **Lighthouse Performance:** 100/100 (averaging 99-100 with network variance)

## 🏗️ Technical Architecture

This plugin is built using **SOLID design principles** to ensure scalability, testability, and maintainability:

```
WordPress Hook
    ↓
DI Container
    ↓
Frontend Service (PriorityService, CleanupService, AssetManager)
    ↓
Image Processor (Strategy Pattern)
    ↓
Format-Specific Implementation (WebP, JPEG, PNG)
```

**Key Design Patterns:**

| Pattern | Implementation | Purpose |
|---------|---|---|
| **Service Pattern** | `PriorityService`, `CleanupService`, `AssetManager` | Encapsulate business logic; enable testing |
| **Strategy Pattern** | `ImageProcessorInterface` + implementations | Support multiple image formats without modification |
| **Registry Pattern** | `ProcessorRegistry` | Manage available processors dynamically |
| **Factory Pattern** | `Container` class | Create service instances with dependency injection |
| **Policy Pattern** | `SettingsPolicy` | Decouple configuration from implementation |
| **Adapter Pattern** | `WordPressAdapter` | Abstract WordPress function calls for testability |

### SOLID Principles Compliance

#### ✅ Single Responsibility Principle (SRP)

**Status:** Fully Implemented

Each class has one reason to change:
- `JpegProcessor` → JPEG optimization only
- `WebpProcessor` → WebP conversion only
- `PriorityService` → LCP detection & preloading only
- `CleanupService` → Asset dequeue only
- `BackupManager` → File backup/restore only

**Evidence:** 50+ classes, each with focused responsibility. No class handles both business logic and I/O simultaneously.

#### ✅ Open/Closed Principle (OCP)

**Status:** Fully Implemented

New image formats can be added without modifying existing code:

```php
// Add custom processor via WordPress filter
add_filter('image_optimizer_processors', function($processors) {
    $processors['avif'] = new CustomAvifProcessor();
    return $processors;
});
```

The `ProcessorRegistry::fromMorphMap()` method allows registration of new processors at runtime. See [EXTENDING.md](docs/EXTENDING.md) for examples.

#### 🟡 Liskov Substitution Principle (LSP)

**Status:** ~95% Implemented

**What's Correct:**
- All `ImageProcessorInterface` implementations throw `OptimizationFailedException` (consistent contract)
- Exception hierarchy ensures callers don't receive unexpected exception types
- All implementations return `bool` (predictable return types)

**What's Being Improved:**
- Standardized exception hierarchy (`ImageOptimizerException` base class) to prevent LSP violations
- All concrete processors now extend the same exception base, guaranteeing substitutability

#### ✅ Interface Segregation Principle (ISP)

**Status:** Fully Implemented

**Interfaces are narrow and focused:**
- `ImageProcessorInterface` → 3 methods (`process`, `supports`, `getMimeType`)
- `WordPressAdapterInterface` → 9 focused methods, grouped by concern
- No "fat" interfaces forcing implementations to have dummy methods

#### 🟡 Dependency Inversion Principle (DIP)

**Status:** ~85% Implemented, Improving

**What's Correct:**
- `Container` provides centralized DI management
- Services receive dependencies through constructors (readonly properties)
- `OptimizationEngine` depends on abstractions (`ProcessorRegistry`), not concrete classes

**What's Being Improved:**
- Frontend services now use `Container::get_service()` instead of `new Service()`
- `WordPressAdapter` abstracts WordPress function calls (injectable for testing)
- PriorityService uses instance state instead of static globals

**Migration Path:** Services are gradually adopting full DI via the Container. See [DEVELOPMENT.md](DEVELOPMENT.md) for testing patterns.

### Why SOLID Matters

**Scalability:** Each new image format (AVIF, HEIC) requires only adding a new processor class. No modification to existing code = lower regression risk.

**Testability:** Services depend on interfaces, enabling mock implementations. `WordPressAdapter` enables testing without WordPress bootstrap.

**Maintainability:** Clear responsibility separation makes debugging faster. A bug in LCP logic only affects `PriorityService`.

**Extensibility:** WordPress filters + registry pattern allow plugins to add custom processors without forking the plugin.

### Architecture Documentation

For detailed architecture patterns and implementation examples, see:
- [CASE_STUDY.md](CASE_STUDY.md) - Performance optimization deep-dive
- [DEVELOPMENT.md](DEVELOPMENT.md) - Development workflow & testing patterns
- [docs/EXTENDING.md](docs/EXTENDING.md) - How to add custom processors
- [docs/REFACTORING.md](docs/REFACTORING.md) - SOLID refactoring implementation details
- [docs/TESTING.md](docs/TESTING.md) - Comprehensive testing guide
- [docs/TEST-PLAN.md](docs/TEST-PLAN.md) - Pre-deployment test checklist

## 🚀 Key Features

- **Deterministic Preloading:** Zero-delay discovery for above-the-fold images.
- **Bloat Removal:** Optional toggles to disable heavy core JS ($60KB+ saved).
- **Consolidated Dashboard:** Manage all performance policies from a single, secure UI.
- **On-Demand Script Loading:** Navigation interactivity deferred until user interaction (keeps TBT at 0ms).
- **Font Optimization:** Local font preloading with `font-display: swap` override (eliminates Flash of Unstyled Text).

## 📦 Installation

1. Download from [WordPress Plugin Directory](https://wordpress.org/plugins/odr-image-optimizer/)
2. Or: `wp plugin install odr-image-optimizer --activate`

## ⚙️ Configuration

Navigate to **Settings → Image Optimizer** to configure:

- **Preload Theme Fonts:** Speed up font discovery by preloading local theme fonts early
- **Disable Core Bloat:** Remove WordPress Emoji and Interactivity API scripts ($60KB+ savings)
- **Inline Critical CSS:** Prevent render-blocking CSS from delaying page load
- **Native Lazy Loading:** Enable browser-native lazy loading for images
- **Remove Emoji Detection Script:** Disable WordPress emoji script (redundant if bloat disabled)
- **Use font-display: swap:** Enable faster font rendering (prevents Flash of Unstyled Text)

## 🔒 Security & Compliance

- ✅ WordPress.org compliant
- ✅ Capability-gated settings (`manage_options`)
- ✅ Nonce verification on all forms
- ✅ Late escaping on all outputs
- ✅ Zero global state pollution
- ✅ ABSPATH protection throughout

## 👨‍💻 Development

### Code Quality

```bash
# Format code
composer run format

# Static analysis (PHPStan Level Max)
composer run analyze

# Run tests
composer run test

# Quick verification (no WordPress needed)
php tests/verify-changes.php

# Full test suite (requires WordPress autoloader)
php tests/run-tests.php all
```

### Architecture Patterns

This plugin demonstrates professional WordPress development:

- **Service Pattern** for dependency injection
- **Strategy Pattern** for image optimization
- **Registry Pattern** for size management
- **Policy Pattern** for settings decoupling
- **Factory Pattern** for service instantiation

### Lighthouse Methodology

The 100/100 score is achieved through "Bandwidth Lane Management Theory":

1. **Identify competing resources** (Emoji script, Interactivity API, fonts)
2. **Remove non-critical assets** from initial load
3. **Preload essentials early** (fonts, LCP image)
4. **Create parallel download lanes** instead of sequential discovery
5. **Result:** Deterministic FCP/LCP, no variance

[Read the case study](CASE_STUDY.md) for technical deep-dive.

## 📊 Performance Impact

| Metric | With Plugin | Without Plugin | Impact |
|--------|-------------|----------------|--------|
| LCP | 1.8s | 2.4s | -25% |
| FCP | 0.8s | 1.2s | -33% |
| TBT | 0ms | 50ms | -100% |
| Lighthouse Performance | 100/100 | 96/100 | +4 points |

**Note:** Metrics measured on WordPress 6.9.1 with Lighthouse 13.0.1 (Mobile/Slow 4G). On-demand navigation script loading defers the 50ms TBT penalty until user interaction, achieving 100/100 without sacrificing functionality.

## 📝 License

GPL v2 or later. See [LICENSE](LICENSE) for details.

## 🙋 Support

- [Documentation](docs/)
- [Testing Guide](docs/TESTING.md)
- [GitHub Issues](https://github.com/odanree/odr-image-optimizer/issues)
- [WordPress.org Support](https://wordpress.org/support/plugin/odr-image-optimizer/)

---

**Author:** Danh Le  
**Email:** danhle@danhle.net  
**Version:** 1.0.2  
**WordPress:** 6.0+  
**PHP:** 8.1+
