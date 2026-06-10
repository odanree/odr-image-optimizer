=== ODR Image Optimizer ===
Contributors: odanree
Tags: images, performance, webp, speed, optimizer
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.12
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional image optimizer: WebP conversion, LCP preloading, and critical-path cleanup for a 100/100 Lighthouse score.

== Description ==

ODR Image Optimizer is a professional-grade performance suite built on SOLID principles. Unlike monolithic optimization plugins, ODR focuses on the critical rendering path to eliminate resource discovery delays.

Designed to hit a 100/100 Lighthouse score on mobile under standard configurations. By decoupling image processing from delivery policy, the plugin reduces Largest Contentful Paint (LCP) by optimizing how the browser prioritizes assets.

**Performance Results (reference test site, mobile Lighthouse):**
- Largest Contentful Paint (LCP): 1.4s (↓ 1.0s improvement)
- First Contentful Paint (FCP): 1.0s
- Total Blocking Time (TBT): 0ms
- Cumulative Layout Shift (CLS): 0

Real-world numbers will vary based on theme, hosting, and content.

== Installation ==

1. Upload the `odr-image-optimizer` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Open the new **ODR Image Optimizer** entry in the WordPress admin sidebar and configure your toggles under the **Settings** submenu.

== Features ==

* **Priority Service:** Injects `fetchpriority="high"` and `<link rel="preload">` tags to bypass LCP discovery delay.
* **WebP Conversion:** Automatically converts and serves images in next-gen formats.
* **Kill Bloat:** Toggle to remove heavy WordPress core scripts like the Interactivity API and Emojis.
* **SOLID Architecture:** Developer-friendly, strictly typed, and built for speed.
* **Unified Dashboard:** Manage all performance settings from a single, secure interface.
* **Zero Configuration:** Works out-of-the-box with sensible defaults optimized for Lighthouse 100/100.

== Frequently Asked Questions ==

= Will this slow down my site? =
No. ODR Image Optimizer adds minimal overhead (~2KB to the page) and removes competing resources. The net result is faster perceived performance and better Lighthouse scores.

= Can I disable specific optimizations? =
Yes. Open **ODR Image Optimizer → Settings** in the WordPress admin sidebar. From there you can:
- Pick a compression level (low / medium / high)
- Enable or disable WebP conversion
- Choose a lazy-load strategy (native / hybrid / off)
- Toggle auto-optimize on upload
- Toggle "Preload Theme Fonts"
- Toggle "Kill Bloat" (removes the Interactivity API and Emoji scripts)
- Toggle "Inline Critical CSS"

= Is this compatible with other image plugins? =
ODR works best as the only image optimization plugin. Having multiple optimization plugins can cause conflicts. Disable any other image optimization plugins before activating ODR.

= What WordPress versions are supported? =
WordPress 6.0+. Requires PHP 8.1+.

= Do I need to re-optimize existing images? =
No. ODR works on all images, new and existing. Simply activate and the plugin handles optimization automatically.

== Screenshots ==

1. Lighthouse audit on the reference test site, desktop: 100 Performance / 97 Accessibility / 96 Best Practices / 100 SEO.
2. Lighthouse audit on the reference test site, mobile: Performance varies with theme and network conditions; this audit captured 91. Accessibility, Best Practices, and SEO remained 100 / 96 / 100.

== Changelog ==

= 1.0.12 =
* Docs: Added directory listing assets — icon, banner (772x250 + 1544x500 retina), and two Lighthouse audit screenshots (desktop + mobile). The mobile screenshot caption hedges the 91 Performance number against the "100/100" headline so the listing is internally consistent.

= 1.0.11 =
* Docs: Renamed "Cleanup Service" to "Kill Bloat" in the Features list to match the actual toggle name in the settings UI.
* Docs: Softened the "100/100 Lighthouse" claim in the Description so it reads as a design goal rather than a per-install guarantee.

= 1.0.10 =
* Docs: Listing copy accuracy pass — admin menu location, FAQ toggle list, missing 1.0.1 entry, 1.0.8 upgrade notice, hedged performance numbers.
* CI: Deploy workflow resolves the SVN tag from `Stable tag:` in `readme.txt` instead of inferring from the branch ref.

= 1.0.8 =
* Fix: Move image backups out of the plugin folder. Backups now live under `wp-content/uploads/odr-image-optimizer/backups/<relative path>/` instead of a `.backups` directory next to each media file, per WordPress.org plugin guidelines. Reverts of images optimized on older versions still read from the legacy location as a one-time fallback.
* Chore: Remove non-permitted distribution files (development sed script, build-artifact zips) from the plugin root.
* Chore: Add `uninstall.php` to remove the plugin's options and backup folder on uninstall.

= 1.0.7 =
* Fix: Move phpcs:ignore for InterpolatedNotPrepared to the SQL string lines; use phpcs:disable/enable blocks for multi-line queries (class-database, WebpDelivery, DatabaseRepository)
* Fix: Remove inline comment text before phpcs:ignore on NonPrefixedConstantFound (odr-image-optimizer.php line 36)

= 1.0.6 =
* Fix: Add phpcs:ignore for confirmed false positives (ExceptionNotEscaped, NonPrefixedConstant/HookName, DirectDatabaseQuery, InterpolatedNotPrepared, is_writable/chmod in background processing context)

= 1.0.5 =
* Fix: Add ABSPATH direct-access guard to all index.php stub files
* Fix: Replace unlink() with wp_delete_file() (WebpConverter, class-optimizer)
* Fix: Replace date() with gmdate() (class-database, DatabaseRepository)
* Fix: Gate error_log() calls behind WP_DEBUG check
* Fix: Sanitize $_SERVER['HTTP_ACCEPT'] via wp_unslash/sanitize_text_field
* Fix: Reduce readme.txt tags to 5; add required short description

= 1.0.4 =
* Fix: Align sanitize_settings() whitelist with registered fields (add remove_emoji, font_swap; remove stale remove_bloat key)
* Fix: Escape WebP URL with esc_url() in the_content filter callback to prevent potential XSS
* Fix: Move Performance Toggles admin page under plugin nav menu instead of wp-admin Settings

= 1.0.3 =
* Fix: Accept array $size in add_webp_picture_element() and render_picture_element() to match WordPress core behavior and prevent TypeError on WooCommerce order emails

= 1.0.2 =
* Fix: Achieve Lighthouse 100/100 with complete WordPress.org compliance
* Fix: Navigation script deferral now works on-demand (click/touch interaction)
* Fix: Settings toggle now fully controls all feature behaviors
* Refactor: Moved navigation deferral from mu-plugin to main plugin service
* Refactor: Complete SOLID principles implementation with DI Container
* Refactor: All WordPress hooks prefixed with `odr_` for global namespace safety
* Docs: Enhanced documentation for WordPress.org submission

= 1.0.1 =
* Polish: Enhanced plugin header metadata for WordPress.org consistency (description, PHP requirement aligned to 8.1, refreshed tags, Plugin URI now points to GitHub).

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

= 1.0.12 =
Adds directory listing assets (icon, banner, screenshots). No plugin code changes.

= 1.0.11 =
Documentation-only release. No plugin code changes.

= 1.0.10 =
Documentation-only release. The listing copy on WordPress.org now matches the actual settings UI; no plugin code changes.

= 1.0.8 =
Image backups have moved out of the plugin folder. Existing backups continue to work; new optimizations write to wp-content/uploads/odr-image-optimizer/backups/ for WordPress.org guideline compliance.

= 1.0.0 =
Initial release. No upgrades needed.

== Support ==

For issues, feature requests, or documentation:
- GitHub: https://github.com/odanree/odr-image-optimizer
- Support Forum: https://wordpress.org/support/plugin/odr-image-optimizer/
- Documentation: https://github.com/odanree/odr-image-optimizer/tree/main/docs

== Credits ==

**Author:** Danh Le
**Website:** https://danhle.net

Built with SOLID principles and modern PHP practices. Inspired by the methodologies of enterprise performance optimization.

== License ==

This plugin is licensed under the GPLv2 or later. See LICENSE file for details.
