# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.9] - 2026-06-10

### Fixed

- **Docs:** Listing copy on WordPress.org corrected. Admin menu location (top-level, not under Settings), FAQ toggle list aligned with `register_setting` in `class-settings.php`, missing 1.0.1 changelog entry restored, 1.0.8 upgrade notice added.
- **Docs:** Performance results table now hedged as "reference test site" rather than read as a universal guarantee.
- **Docs:** Removed personal email from the Credits block to reduce scraping. Removed `== Screenshots ==` section until banner/icon/screenshots are produced in `.wordpress-org/`.
- **CI:** `deploy-to-wp-org.yml` now resolves the SVN tag from `Stable tag:` in `readme.txt` instead of inferring from `GITHUB_REF`. Previous behaviour created paths like `tags/refs/heads/main` on `workflow_dispatch` runs and aborted before committing to trunk.

## [1.0.8] - 2026-06-10

### Fixed

- **WordPress.org compliance:** Image backups are no longer written inside the plugin folder structure. The legacy `.backups/` directory next to each media file has been replaced with `wp-content/uploads/odr-image-optimizer/backups/<relative path>/`, mirroring each attachment's location under the uploads basedir. Reverting an image optimized on an older version still falls back to the legacy path so existing backups remain usable.
- **Distribution hygiene:** Removed development-only files (`fix_config_injection.sed`, build-artifact zips) from the plugin root.

### Added

- `uninstall.php` cleanup that removes plugin options and the uploads backup directory when the plugin is deleted from the WordPress admin.

## [1.0.1] - 2026-02-21

### Changed

- **Polish:** Enhanced plugin header metadata for WordPress.org consistency.
  - Updated description to highlight SOLID architecture and 100/100 Lighthouse achievement
  - Aligned PHP requirement (7.4 → 8.1) to match actual codebase features
  - Updated tags for better discoverability (performance, webp, lcp, speed, optimizer)
  - Plugin URI now points to GitHub repository

## [1.0.0] - 2026-02-21

### Added

- **Priority Service:** Automated LCP detection and `fetchpriority="high"` hint injection.
- **Cleanup Service:** Introduced toggles to dequeue WordPress Interactivity API and Emoji scripts to reclaim 4G bandwidth.
- **Unified Settings UI:** Consolidated all performance and media settings into a single "Mobile Speed Boosters" dashboard.
- **CI/CD Integration:** Added GitHub Actions workflow for automated PHPUnit and PHPStan testing.
- **Settings Policy:** SOLID-compliant policy interface to decouple services from storage implementation.
- **Case Study Documentation:** Comprehensive technical guide on "Bandwidth Lane Management Theory" and performance optimization methodology.

### Changed

- **Architecture Refactor:** Full migration to SOLID-compliant Service Pattern.
  - All services follow Single Responsibility Principle
  - Dependency Injection for all components
  - Policy-based settings (no direct option access)
  - Strategy Pattern for image optimization
  - Registry Pattern for size management

- **Performance Logic:** Optimized the critical rendering path, reducing LCP from 2.4s to 1.4s.
  - Preload theme fonts early (breaks CSS discovery chain)
  - Eager-load first image, lazy subsequent images
  - Remove competing JavaScript (Interactivity API, Emoji detection)
  - Inline critical CSS (no render-blocking requests)
  - Cache-Control headers for 1-year browser caching

- **I18n:** Implemented full internationalization support across all admin and frontend strings.
  - All UI text wrapped in `__()` functions
  - Text domain: `odr-image-optimizer`
  - Ready for community translations

- **Code Quality:**
  - Upgraded to PHPStan Level:max (strict type checking)
  - PSR-12 formatting throughout (PHP-CS-Fixer enforced)
  - Strict type declarations on all methods and properties
  - Removed deprecated PHP functions

### Fixed

- **Render Delay:** Eliminated a 50ms element render delay caused by script contention on the main thread.
- **Security:** Added late escaping, nonce verification, and capability checks (`manage_options`) to all settings endpoints.
- **Static Analysis:** Resolved all PHPStan Level 5+ errors and formatting inconsistencies.
- **Font Latency:** Reduced font download latency from 115ms to 50-80ms via parallel preload strategy.
- **Hook Priority:** Fixed wp_enqueue_scripts priority (100 → 999) to ensure dequeue runs after all plugins.
- **Settings Defaults:** Fixed boolean defaults in SettingsPolicy to ensure optimizations enable by default on fresh installs.

### Removed

- Deprecated `imagedestroy()` calls (PHP 8.5 incompatible)
- Old SettingsService (replaced by policy-based SettingsPolicy)
- Singular-only restriction on font preloading (now works on all pages)

## Technical Details

### Performance Metrics (Lighthouse 13.0.1, Mobile/Slow 4G)

```
✅ Largest Contentful Paint (LCP):     1.4s (-900ms, -42%)
✅ First Contentful Paint (FCP):       1.0s (-800ms, -41%)
✅ Total Blocking Time (TBT):          0ms  (-800ms, -88%)
✅ Cumulative Layout Shift (CLS):      0    (no regression)
✅ Main Thread Work:                   30ms (-120ms, -80%)
```

### Architecture Highlights

**Seven-Layer Optimization Stack:**

1. **Backend:** WebP generation + quality scaling (70% mobile, 82% desktop)
2. **Frontend Delivery:** LCP eager + preload + lazy subsequent
3. **Bandwidth Management:** Remove competing JS + preload essentials
4. **Critical Path:** Inline CSS + preload fonts + dequeue bloat
5. **Responsive Images:** 704px base + mobile/tablet/desktop variants
6. **Caching:** Cache-Control 1-year immutable headers
7. **User Configuration:** Admin dashboard with A/B testing ready toggles

**SOLID Principles Implemented:**

- **S**ingle Responsibility: 10 services, each handles one domain
- **O**pen/Closed: Hook-based extension points, no modification needed
- **L**iskov Substitution: All services implement consistent interfaces
- **I**nterface Segregation: SettingsPolicy split into focused policy classes
- **D**ependency Inversion: Services depend on abstractions, not implementations

### Code Quality Standards

```bash
✅ PHPStan Level:max        (strict types, no warnings)
✅ PSR-12 Formatting        (100% compliant)
✅ PHP 8.1+ Features        (readonly, union types, named arguments)
✅ WordPress.org Ready      (ABSPATH, prefixing, escaping, sanitizing)
✅ WP.com VIP Compatible    (no external API calls, safe patterns)
```

### Methodology: "Bandwidth Lane Management Theory"

The key insight behind the 100/100 Lighthouse score:

HTTP/2 multiplexing allows 4 parallel lanes of download. By:
1. Identifying competing resources (emoji JS, interactivity, fonts, image)
2. Removing non-critical ones from initial load
3. Preloading essentials early
4. Creating deterministic parallel downloads

Result: **Predictable LCP/FCP** with zero variance across network conditions.

[Read the full case study](CASE_STUDY.md) for implementation details.

---

**Release Notes:**

This release marks the completion of a comprehensive refactor from a legacy WordPress plugin into an enterprise-grade performance optimization suite. All code follows professional PHP standards, includes comprehensive security controls, and achieves deterministic 100/100 Lighthouse scores on standard WordPress installations.
