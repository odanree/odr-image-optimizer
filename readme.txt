=== ODR Image Optimizer ===
Contributors: odanree
Tags: images, performance, webp, lcp, speed, optimizer
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

ODR Image Optimizer is a professional-grade performance suite built on SOLID principles. Unlike monolithic optimization plugins, ODR focuses on the critical rendering path to eliminate resource discovery delays.

Achieve a deterministic 100/100 Lighthouse score on mobile. By decoupling image processing from delivery policy, this plugin reduces Largest Contentful Paint (LCP) by optimizing how the browser prioritizes assets.

**Performance Results:**
- Largest Contentful Paint (LCP): 1.4s (↓ 1.0s improvement)
- First Contentful Paint (FCP): 1.0s
- Total Blocking Time (TBT): 0ms
- Cumulative Layout Shift (CLS): 0

== Installation ==

1. Upload the `odr-image-optimizer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your Speed Boosters under Settings > ODR Image Optimizer.

== Features ==

* **Priority Service:** Injects `fetchpriority="high"` and `<link rel="preload">` tags to bypass LCP discovery delay.
* **WebP Conversion:** Automatically converts and serves images in next-gen formats.
* **Cleanup Service:** Toggles to remove heavy WordPress core scripts like the Interactivity API and Emojis.
* **SOLID Architecture:** Developer-friendly, strictly typed, and built for speed.
* **Unified Dashboard:** Manage all performance settings from a single, secure interface.
* **Zero Configuration:** Works out-of-the-box with sensible defaults optimized for Lighthouse 100/100.

== Frequently Asked Questions ==

= Will this slow down my site? =
No. ODR Image Optimizer adds minimal overhead (~2KB to the page) and removes competing resources. The net result is faster perceived performance and better Lighthouse scores.

= Can I disable specific optimizations? =
Yes. Navigate to Settings > ODR Image Optimizer to toggle individual features:
- Preload Theme Fonts
- Kill Bloat (remove Interactivity API/Emojis)
- Inline Critical CSS
- Lazy Load Delivery

= Is this compatible with other image plugins? =
ODR works best as the only image optimization plugin. Having multiple optimization plugins can cause conflicts. Disable any other image optimization plugins before activating ODR.

= What WordPress versions are supported? =
WordPress 6.0+. Requires PHP 8.1+.

= Do I need to re-optimize existing images? =
No. ODR works on all images, new and existing. Simply activate and the plugin handles optimization automatically.

== Screenshots ==

1. Unified Performance Dashboard
2. Settings Configuration Interface
3. Lighthouse 100/100 Achievement

== Changelog ==

= 1.0.0 =
* Initial release.
* Added Priority Service for LCP optimization.
* Added Cleanup Service for bandwidth reclamation.
* Consolidated settings into a unified dashboard.
* Implemented SOLID architecture for maintainability.
* Added comprehensive case study documentation.
* Full internationalization support (i18n ready).
* Security audit complete: nonce verification, capability checks, late escaping.
* PHPStan Level Max compliance (strict types throughout).
* PSR-12 formatting enforced via CI/CD.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrades needed.

== Support ==

For issues, feature requests, or documentation:
- GitHub: https://github.com/odanree/odr-image-optimizer
- Support Forum: https://wordpress.org/support/plugin/odr-image-optimizer/
- Documentation: https://github.com/odanree/odr-image-optimizer/tree/main/docs

== Credits ==

**Author:** Danh Le
**Email:** danhle@danhle.net
**Website:** https://danhle.net

Built with SOLID principles and modern PHP practices. Inspired by the methodologies of enterprise performance optimization.

== License ==

This plugin is licensed under the GPLv2 or later. See LICENSE file for details.
