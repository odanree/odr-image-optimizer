# Security Policy

## Reporting Vulnerabilities

If you discover a security vulnerability in ODR Image Optimizer, please report it responsibly by sending an email to **security@danhle.net** instead of using the issue tracker.

**Please include:**
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if available)

We will acknowledge receipt within 24 hours and provide a timeline for resolution.

## Security Practices

### Authentication & Authorization

- **Capability Checks:** All sensitive operations require `manage_options` capability
- **Nonce Verification:** All AJAX requests and form submissions are protected with nonces
- **No Authentication Bypass:** All security checks are mandatory and cannot be bypassed

### Data Protection

- **Input Sanitization:** All user input is sanitized using WordPress APIs (`sanitize_text_field()`, `absint()`, etc.)
- **Output Escaping:** All output is properly escaped using WordPress functions (`esc_html()`, `esc_url()`, `wp_kses_post()`)
- **File Operations:** All file operations use WordPress Filesystem API for security and permission handling

### Database Security

- **SQL Injection Prevention:** All database queries use `$wpdb->prepare()` for parameterized queries
- **No Direct Queries:** All database access goes through WordPress APIs

### File Security

- **Direct Access Prevention:** All PHP files include `if ( ! defined( 'ABSPATH' ) ) exit;` guard
- **Filesystem API:** Uses `WP_Filesystem` for all file operations with proper permission handling
- **No World-Writable Files:** All files are created with appropriate permissions (0644 for files, 0755 for directories)

## Security Headers

The plugin respects and works with WordPress security headers including:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `X-XSS-Protection: 1; mode=block`

## Third-Party Dependencies

- **Composer Dependencies:** All dependencies are regularly updated
- **Vulnerability Scanning:** Project is scanned with automated security tools

## WordPress.org Compliance

This plugin adheres to [WordPress Plugin Directory guidelines](https://developer.wordpress.org/plugins/security/) and passes the Plugin Check (PCP) security scanner.

**Security Checklist:**
- ✅ ABSPATH guards on all files
- ✅ Proper capability checks on all admin operations
- ✅ Nonce verification on all form submissions
- ✅ Input sanitization with `sanitize_*` functions
- ✅ Output escaping with `esc_*` functions
- ✅ Use of WordPress Filesystem API for file operations
- ✅ SQL injection prevention with `$wpdb->prepare()`
- ✅ No deprecated WordPress functions
- ✅ Proper hook prefixing (all custom hooks use `image_optimizer_` prefix)
- ✅ No direct database access outside of WordPress APIs

## Security Testing

The plugin has been tested with:
- **PHPStan:** Static analysis at level max (strictest)
- **WordPress Plugin Check (PCP):** Automated security scanner
- **Manual Security Review:** Comprehensive code review for security vulnerabilities

## Version History

### Version 1.1.0
- Added WordPress Filesystem API for all file operations
- Enhanced output escaping for security
- Comprehensive capability documentation in method docblocks

### Version 1.0.2
- Initial security audit and compliance improvements

## Contact

For security-related questions or concerns, contact: security@danhle.net

---

**Last Updated:** February 22, 2026  
**Plugin Version:** 1.1.0+  
**Status:** Actively Maintained
