# Project Structure

This document outlines the organization of the ODR Image Optimizer plugin following industry standards for maintainability and clarity.

## Root Level Files

### Core Documentation (Root)
Only essential documentation files live in the root directory:

| File | Purpose | Audience |
|------|---------|----------|
| `README.md` | Project overview, badges, quick start | Everyone |
| `CHANGELOG.md` | Version history and release notes | Users, Contributors |
| `CONTRIBUTING.md` | Contribution guidelines and workflow | Contributors |
| `DEVELOPMENT.md` | Development setup and local testing | Developers |
| `CASE_STUDY.md` | Performance optimization deep-dive | Architects, Performance engineers |
| `LICENSE` | GPL 2.0+ license | Legal, Users |

### Plugin Entry Point
| File | Purpose |
|------|---------|
| `odr-image-optimizer.php` | Main plugin file - WordPress entry point |
| `readme.txt` | WordPress.org plugin directory metadata |

### Configuration Files
| File | Purpose |
|------|---------|
| `composer.json` | PHP dependencies |
| `phpunit.xml` | PHPUnit test configuration |
| `phpstan.neon` | PHPStan static analysis config |
| `.php-cs-fixer.php` | Code formatting configuration |

## Directory Structure

### `/includes` - Source Code
Main plugin codebase following standard WordPress structure:

```
includes/
├── core/
│   ├── class-container.php        # Dependency Injection Container
│   ├── class-plugin.php           # Plugin orchestrator
│   └── class-performance-monitor.php
├── Services/
│   ├── class-priority-service.php      # LCP detection & preloading
│   ├── class-cleanup-service.php       # Asset dequeue
│   ├── class-asset-manager.php         # Script/style loading
│   └── class-optimization-engine.php
├── ImageProcessors/
│   ├── class-image-processor-interface.php
│   ├── class-jpeg-processor.php
│   ├── class-webp-processor.php
│   └── class-png-processor.php
├── Exception/
│   ├── class-image-optimizer-exception.php      # Base exception
│   ├── class-optimization-failed-exception.php
│   ├── class-backup-failed-exception.php
│   └── class-processor-not-available-exception.php
├── Adapter/
│   ├── class-wordpress-adapter-interface.php    # WordPress abstraction
│   └── class-wordpress-adapter.php
├── Admin/
│   ├── class-settings-page.php
│   └── class-debug-info.php
└── Utilities/
    ├── class-settings-policy.php
    ├── class-backup-manager.php
    └── class-processor-registry.php
```

### `/docs` - Extended Documentation
Detailed guides and references (industry standard location):

```
docs/
├── EXTENDING.md                   # How to add custom processors
├── REFACTORING.md                 # SOLID refactoring details
├── TESTING.md                     # Testing methodology
├── TEST-PLAN.md                   # Pre-deployment checklist
├── IMPLEMENTATION_SUMMARY.md      # Refactoring summary
├── DEVELOPMENT.md                 # (Also in root, referenced here)
├── LIGHTHOUSE_OPTIMIZATIONS.md    # LCP/FCP optimization details
├── PERFORMANCE_CASE_STUDY.md      # Technical deep-dive
├── COMMIT_CONVENTION.md           # Git commit standards
├── WORDPRESS_ORG_SUBMISSION.md    # WordPress.org guide
└── CI-CD.md                       # (In .github/, CI/CD pipelines)
```

### `/tests` - Testing Infrastructure
Test files and verification scripts:

```
tests/
├── bootstrap.php                  # PHPUnit bootstrap
├── README_TESTS.md                # Test guide
├── LcpGuardTest.php               # LCP Service tests
├── OptimizationConfigTest.php     # Config tests
├── test-solid-refactoring.php     # SOLID principle tests
├── run-tests.php                  # Test runner script
├── verify-changes.php             # Quick verification (no WordPress needed)
└── fixtures/
    └── sample-images/             # Test images
```

### `/assets` - Frontend Assets
Static resources:

```
assets/
├── css/
│   └── admin.css
├── js/
│   ├── admin.js
│   └── frontend.js
└── images/
    └── plugin-icon.svg
```

### `.github` - GitHub Configuration
CI/CD and repository settings:

```
.github/
├── workflows/
│   ├── quality.yml                # Code quality & tests
│   ├── release.yml                # Automated releases
│   └── security.yml               # Security scanning
├── CI-CD.md                       # GitHub Actions documentation
├── CODE_OF_CONDUCT.md
├── FUNDING.yml
└── ISSUE_TEMPLATE/
```

### `/vendor` - Dependencies (Generated)
Third-party PHP packages installed via Composer.
*Never manually edit - `composer install/update` only.*

## File Organization Principles

### Root Level Constraints
✅ **Only these file types in root:**
- Core documentation: `README.md`, `CHANGELOG.md`, `CONTRIBUTING.md`, `DEVELOPMENT.md`
- Entry points: `odr-image-optimizer.php`, `readme.txt`
- Configuration: `composer.json`, `phpunit.xml`, etc.
- Version control: `.gitignore`, `.git/`

❌ **NOT in root:**
- Test files → `/tests/`
- Extended documentation → `/docs/`
- Test utilities/verification scripts → `/tests/`
- Local configuration → `.gitignore` + avoid tracking

### Why This Matters

**Clarity:** Developers immediately understand:
- Root = essential, quick reference
- `/docs/` = deep dives, implementation details
- `/includes/` = source code
- `/tests/` = test infrastructure

**Scalability:** As plugin grows from 1K to 100K lines:
- Root remains clean and manageable
- New features fit naturally into existing structure
- Contributors find things quickly

**Standards Compliance:** Follows:
- WordPress plugin conventions
- PHP package standards (PSR-4 structure in `/includes/`)
- Industry documentation best practices
- Comparable to major frameworks (Laravel, Symfony)

## Quick Navigation

| Task | Location |
|------|----------|
| Quick verification | `php tests/verify-changes.php` |
| Run all tests | `php tests/run-tests.php all` |
| View test plan | `docs/TEST-PLAN.md` |
| Extend plugin | `docs/EXTENDING.md` |
| Understand architecture | `CASE_STUDY.md` → `docs/REFACTORING.md` |
| Set up development | `DEVELOPMENT.md` |
| Deploy to production | `docs/TEST-PLAN.md` (checklist) |

## Adding New Files

**New source code?** → `/includes/{Category}/`
```php
includes/Services/class-my-new-service.php
```

**New documentation?** → `/docs/`
```
docs/FEATURE_NAME.md
```

**New tests?** → `/tests/`
```php
tests/MyNewServiceTest.php
```

**New assets?** → `/assets/{type}/`
```
assets/css/new-feature.css
assets/js/new-feature.js
```

## Verification

Run this to verify structure compliance:

```bash
# Quick check (no WordPress needed)
php tests/verify-changes.php

# Full structure validation
ls -la includes/ docs/ tests/ .github/ assets/
```

All 8+ verification checks should pass ✅

---

**Last Updated:** February 22, 2026  
**Status:** Follows industry standards (LLM-Assistant model)
