=== ODR Image Optimizer ===
Contributors: odanree
Donate link: https://danhle.net/
Tags: image-optimization, image-compression, webp, lazy-loading, performance
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional WordPress image optimization with intelligent compression, WebP conversion, and lazy loading.

== Description ==

ODR Image Optimizer is a production-ready WordPress plugin demonstrating enterprise-level development practices. It provides intelligent image compression, WebP conversion, lazy loading, and REST API integration for modern image optimization workflows.

**Key Features:**

* **Multi-level Compression** - Low, Medium, and High quality settings for flexible optimization
* **Format Support** - JPEG, PNG, GIF, and WebP conversion with automatic browser fallbacks
* **Smart Optimization** - Compression algorithms adapted per image type for optimal results
* **Lazy Loading** - Intersection Observer API for improved Core Web Vitals
* **Async Processing** - Non-blocking image optimization with background task support
* **Admin Dashboard** - Real-time statistics, visual library view, and batch operations
* **REST API** - Complete API for programmatic access and integrations
* **Responsive Design** - Mobile-friendly admin interface for all devices
* **WebP Generation** - Automatic WebP creation with PNG/JPEG fallbacks
* **Batch Processing** - One-click optimization for single or bulk operations

**Performance Results:**

Verified via Lighthouse testing on mobile 4G with 5 high-resolution test images:

* **Performance Score:** +9 points (82 → 91)
* **LCP Improvement:** -1.3s on mobile (4.4s → 3.1s)
* **Desktop Performance:** Perfect 100 score achieved
* **Image Delivery:** 83% reduction (115 KiB saved)

**Developer-Friendly:**

* PHP 7.4+ with modern OOP design patterns
* WordPress coding standards compliant
* Comprehensive REST API with authentication
* Extensible architecture with hooks for custom processing
* Well-documented code with inline PHPDoc comments
* Complete admin dashboard UI built with vanilla JavaScript

**Perfect For:**

* WordPress developers building portfolio projects
* Agencies managing client WordPress sites
* Freelancers offering optimization services
* Site owners focused on Core Web Vitals
* Developers learning WordPress plugin best practices

**WordPress Compatibility:**

* WordPress 5.0 and above
* Multisite compatible
* Works with custom upload directories
* Compatible with WooCommerce media
* REST API enabled sites

**License:**

GPL v2 or later. See LICENSE file for details.

== Installation ==

1. Upload the `image-optimizer` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Admin → Plugins page
3. Navigate to **Image Optimizer → Settings**
4. Choose your compression level (Low/Medium/High)
5. Enable WebP conversion (optional)
6. Enable lazy loading (optional)
7. Save changes

**No additional configuration needed!** The plugin works out-of-the-box with sensible defaults.

== Usage ==

= Dashboard View =

1. Go to **Image Optimizer → Dashboard**
2. View your media library with optimization status
3. Click **Optimize** on any image or select multiple for bulk operation
4. Monitor real-time statistics and compression results

= Settings Configuration =

1. Go to **Image Optimizer → Settings**
2. **Compression Level** - Choose Low (high quality), Medium (balanced), or High (smaller size)
3. **WebP Conversion** - Enable to create WebP versions with automatic browser fallbacks
4. **Lazy Loading** - Enable for improved page load performance
5. **Auto-Optimize on Upload** - Optional: Automatically optimize new images on upload

= REST API =

Programmatic access via REST endpoints:

```
GET /wp-json/image-optimizer/v1/stats
GET /wp-json/image-optimizer/v1/images
POST /wp-json/image-optimizer/v1/optimize/{attachment_id}
GET /wp-json/image-optimizer/v1/history/{attachment_id}
```

Requires `manage_options` capability.

== Frequently Asked Questions ==

= Is this production-ready? =

Yes! The plugin follows WordPress best practices and security standards. It's used on production WordPress sites and is suitable for agencies and freelancers.

= Does it work with all WordPress versions? =

The plugin requires WordPress 5.0 and PHP 7.4+. Tested and verified up to WordPress 6.9.

= Can I use this commercially? =

Yes! It's GPL v2+, so you can use it in commercial projects. Just maintain the license.

= How much does it improve performance? =

Verified via Lighthouse testing: **+9 performance points** (82→91) and **30% LCP improvement** (4.4s→3.1s) on mobile with test images. See Performance Metrics section in README.

= What image formats are supported? =

JPEG, PNG, GIF, and WebP. The plugin automatically handles conversions and maintains compatibility.

= Does it work with WooCommerce? =

Yes! You can optimize product images, thumbnails, and gallery images alongside regular media library images.

= Can I extend the plugin? =

Absolutely! Check the developer documentation for hooks and filters. See docs/DEVELOPMENT.md for examples.

= What if optimization fails? =

All errors are logged to WordPress debug log. Check wp-content/debug.log for details. Common issues include memory limits on shared hosting.

= Does it work on multisite? =

The plugin works on multisite WordPress installations. Each site maintains separate optimization records.

= Can I undo optimizations? =

Yes! The plugin creates backups by default. Use the **Revert** option to restore to the original.

= How do I report bugs? =

Visit the support forum or report issues on GitHub: https://github.com/odanree/image-optimizer/issues

= Is there a free tier? =

Yes! Image Optimizer is entirely free. No premium features or upsells.

== Screenshots ==

1. Admin Dashboard - Real-time optimization statistics and visual media library view with optimization status
2. Image Library - Bulk optimization interface showing images with compression results and savings
3. Settings Page - Configuration options for compression level, WebP conversion, and lazy loading

== Changelog ==

= 1.0.0 =
* Initial release
* Image compression with multi-level quality settings (Low/Medium/High)
* WebP conversion with automatic browser fallbacks
* Lazy loading with Intersection Observer API
* Admin dashboard with real-time statistics
* Visual media library view with optimization status
* Batch image optimization
* One-click revert functionality with backup management
* Complete REST API implementation
* WordPress coding standards compliance
* Comprehensive admin documentation
* Performance verified: +9 Lighthouse score improvement

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and activate to get started with professional image optimization.

== Development & Support ==

**GitHub Repository:** https://github.com/odanree/image-optimizer

**Contributing:** See CONTRIBUTING.md for guidelines

**Documentation:** 
* User Guide: README.md
* Developer Guide: docs/DEVELOPMENT.md
* WordPress.org Submission: docs/WORDPRESS_ORG_SUBMISSION.md

**Author:** Danh Le
**Website:** https://danhle.net/
**Email:** hello@danhle.net

== Special Thanks ==

Thanks to the WordPress community, PHP CodeSniffer team, and all contributors who helped make this plugin production-ready.

Built with ❤️ for WordPress developers.
