# ODR Image Optimizer

> **The High-Performance Image Engine for Modern WordPress.**

[![WordPress Plugin Directory](https://img.shields.io/badge/WordPress.org-live-21759b?style=for-the-badge&logo=wordpress)](https://wordpress.org/plugins/odr-image-optimizer/)
![Lighthouse 100](https://img.shields.io/badge/Lighthouse-100-brightgreen?style=for-the-badge&logo=googlechrome)
![PHP 8.1+](https://img.shields.io/badge/PHP-8.1+-777bb4?style=for-the-badge&logo=php)
![License GPL 2.0](https://img.shields.io/badge/license-GPL%202.0-blue.svg?style=for-the-badge)

ODR Image Optimizer is a **SOLID-compliant** performance suite designed to reclaim the critical rendering path. By decoupling image processing from delivery policy, it achieves a **1.4s LCP** on throttled mobile connections with **100/100 Lighthouse Performance**.

> **Available on the WordPress.org Plugin Directory** as of June 2026: [wordpress.org/plugins/odr-image-optimizer](https://wordpress.org/plugins/odr-image-optimizer/). New releases ship automatically from `v*` git tags via a GitHub Actions → SVN bridge (see [Release Flow](#-release-flow)).

## ⚡ Performance Benchmarks

Tested on a standard WordPress 6.9.1 installation using Lighthouse 13.0.1 (Mobile/Slow 4G) with on-demand navigation script loading. Results from the reference test site; real-world numbers will vary by theme, hosting, and content.

- **Largest Contentful Paint (LCP):** 1.4s (↓ 1.0s improvement)
- **First Contentful Paint (FCP):** 1.0s
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

### Optimization Strategy: Multi-Tier Performance

This plugin uses a **hybrid optimization strategy** that respects the SOLID principles:

**Tier 1: Intelligent Deferral (NavigationDeferralService)**
- Interactivity API scripts are **deferred until first user interaction** (touches, clicks)
- Fallback: 5-second timeout for passive users
- This preserves full functionality while keeping critical rendering path clean
- **Responsibility:** Performance optimization (the "how" of loading)

**Tier 2: Feature Management (plugin setting)**
- Core plugin provides toggle to **enable/disable** the deferral strategy
- Emoji detection scripts are removed when enabled
- When disabled, all scripts load normally (no deferral)
- **Responsibility:** Feature management (the "if" of loading)

**Why This Approach:**
- **SRP:** Each component has a single responsibility (loading strategy vs feature toggle)
- **Least Astonishment:** Users understand scripts are being deferred (still available), not removed entirely
- **Flexibility:** Developers can choose whether to optimize for Lighthouse (defer) or for traditional loading
- **Sustainability:** Modern dependencies (Interactivity API) are preserved but optimized, rather than ripped out

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

#### ✅ Liskov Substitution Principle (LSP)

**Status:** Fully Implemented

- All `ImageProcessorInterface` implementations throw `OptimizationFailedException` (consistent contract)
- Exception hierarchy (`ImageOptimizerException` base class) prevents LSP violations across processors
- All concrete processors extend the same exception base, guaranteeing substitutability
- All implementations return `bool` (predictable return types)

#### ✅ Interface Segregation Principle (ISP)

**Status:** Fully Implemented

**Interfaces are narrow and focused:**
- `ImageProcessorInterface` → 3 methods (`process`, `supports`, `getMimeType`)
- `WordPressAdapterInterface` → 9 focused methods, grouped by concern
- No "fat" interfaces forcing implementations to have dummy methods

#### ✅ Dependency Inversion Principle (DIP)

**Status:** Fully Implemented

- `Container` provides centralized DI management
- Services receive dependencies through constructors (readonly properties)
- `OptimizationEngine` depends on abstractions (`ProcessorRegistry`), not concrete classes
- Frontend services use `Container::get_service()` instead of `new Service()`
- `WordPressAdapter` abstracts WordPress function calls (injectable for testing)
- `PriorityService` uses instance state instead of static globals

See [DEVELOPMENT.md](DEVELOPMENT.md) for testing patterns that exercise these abstractions.

### Why SOLID Matters

**Scalability:** Each new image format (AVIF, HEIC) requires only adding a new processor class. No modification to existing code = lower regression risk.

**Testability:** Services depend on interfaces, enabling mock implementations. `WordPressAdapter` enables testing without WordPress bootstrap.

**Maintainability:** Clear responsibility separation makes debugging faster. A bug in LCP logic only affects `PriorityService`.

**Extensibility:** WordPress filters + registry pattern allow plugins to add custom processors without forking the plugin.

### Architecture Documentation

For detailed architecture patterns and implementation examples, see:
- [CLAUDE.md](CLAUDE.md) - Project conventions, release flow, and the WordPress.org compliance constraint for backup storage
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

1. Install from the [WordPress Plugin Directory](https://wordpress.org/plugins/odr-image-optimizer/) — search for "ODR Image Optimizer" inside wp-admin → Plugins → Add New
2. Or via WP-CLI: `wp plugin install odr-image-optimizer --activate`
3. Or clone this repo into `wp-content/plugins/odr-image-optimizer/` for development

## ⚙️ Configuration

After activation, open the new **ODR Image Optimizer** entry in the WordPress admin sidebar (top-level menu, not under Settings) and configure your toggles under the **Settings** submenu:

### Image Optimization (Media Policy)
Control how images are processed and optimized on upload.

- **Compression Level:** Choose between Low (better quality), Medium (balanced), or High (maximum compression)
- **WebP Format Support:** Automatically convert images to WebP format for better compression
- **Auto-Optimize on Upload:** Automatically optimize images when they're uploaded to the media library

### Frontend Performance (Delivery Policy)
Control how images and scripts are delivered and rendered on the frontend.

- **Lazy Loading Mode:** Choose Native (browser-based `loading="lazy"`), Hybrid (with JS fallback for older browsers), or Disabled
- **Preload Theme Fonts:** Preload theme fonts to prevent Flash of Unstyled Text and improve perceived performance
- **Kill Bloat:** Defer non-essential JavaScript to improve Lighthouse performance. When enabled, Emoji detection scripts are removed and Interactivity API scripts are deferred to first user interaction (touch, click) with a 5-second fallback for passive users. This keeps the critical rendering path clean while preserving full functionality. Result: 100/100 Lighthouse + interactive features remain available.
- **Inline Critical CSS:** Inline critical CSS above-the-fold to reduce external CSS requests and unblock rendering

## 🔒 Security & Compliance

- ✅ WordPress.org compliant (passed manual Plugins Team review, June 2026)
- ✅ Capability-gated settings (`manage_options`)
- ✅ Nonce verification on all forms
- ✅ Late escaping on all outputs
- ✅ Zero global state pollution
- ✅ ABSPATH protection throughout
- ✅ Backup storage in `wp_upload_dir()/odr-image-optimizer/backups/` — never inside the plugin folder (per [WordPress.org plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/))
- ✅ `uninstall.php` cleans up plugin options and the uploads backup directory on plugin delete

## 🚢 Release Flow

GitHub is the source of truth; WordPress.org distributes via SVN. The two are bridged by [`.github/workflows/deploy-to-wp-org.yml`](.github/workflows/deploy-to-wp-org.yml), which runs on every `v*` tag push:

1. Checks out the repo
2. Reads `Stable tag:` from `readme.txt` (so version derivation works under both `tag push` and `workflow_dispatch`)
3. Hands off to [`10up/action-wordpress-plugin-deploy`](https://github.com/10up/action-wordpress-plugin-deploy), which:
   - Syncs `main` → SVN `trunk/` (filtered by `.distignore`)
   - Mirrors `.wordpress-org/` → SVN's sibling `assets/` folder (icon, banner, screenshots — never shipped inside the plugin zip)
   - Creates the matching SVN tag

Cutting a release is one line:

```bash
git tag v1.0.X && git push origin v1.0.X
```

The workflow expects two repo secrets:
- `WPORG_SVN_USERNAME` — `odanree`
- `WPORG_SVN_PASSWORD` — generated in the WordPress.org account settings (separate from the wp.org login password)

See [CLAUDE.md](CLAUDE.md) for the full release checklist (version bump locations, changelog format, upgrade notice convention).

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
| LCP | 1.4s | 2.4s | -42% |
| FCP | 1.0s | 1.8s | -44% |
| TBT | 0ms | 50ms | -100% |
| Lighthouse Performance | 100/100 | 96/100 | +4 points |

**Note:** Metrics measured on WordPress 6.9.1 with Lighthouse 13.0.1 (Mobile/Slow 4G). Results from the reference test site; numbers will vary by theme, hosting, and content. On-demand navigation script loading defers the 50ms TBT penalty until user interaction, achieving 100/100 without sacrificing functionality.

## 📝 License

GPL v2 or later. See [LICENSE](LICENSE) for details.

## 🙋 Support

- [Documentation](docs/)
- [Testing Guide](docs/TESTING.md)
- [GitHub Issues](https://github.com/odanree/odr-image-optimizer/issues)
- [WordPress.org Support](https://wordpress.org/support/plugin/odr-image-optimizer/)

---

**Author:** Danh Le ([danhle.net](https://danhle.net))  
**Version:** 1.0.12  
**WordPress:** 6.0+  
**PHP:** 8.1+
