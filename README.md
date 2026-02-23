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

This plugin is built using the **Service Pattern** to ensure strict adherence to SOLID principles:

1. **Priority Service:** Injects `fetchpriority="high"` and `<link rel="preload">` tags into the `<head>` at priority `1`.
2. **Cleanup Service:** Aggressively dequeues non-critical assets (Interactivity API, Emojis) to free up bandwidth.
3. **Image Manager:** Handles WebP conversion and attribute injection using optimized buffer logic.

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

- **Preload Theme Fonts:** Enable font preloading to break CSS discovery chain
- **Kill Bloat:** Remove Interactivity API and Emoji detection scripts
- **Inline Critical CSS:** Eliminate render-blocking CSS requests
- **Lazy Load Delivery:** Choose native, hybrid, or off

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
- [GitHub Issues](https://github.com/odanree/odr-image-optimizer/issues)
- [WordPress.org Support](https://wordpress.org/support/plugin/odr-image-optimizer/)

---

**Author:** Danh Le  
**Email:** danhle@danhle.net  
**Version:** 1.0.2  
**WordPress:** 6.0+  
**PHP:** 8.1+
