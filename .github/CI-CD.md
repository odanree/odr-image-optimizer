# GitHub Actions CI/CD

This project uses GitHub Actions for automated code quality, testing, and releases.

## Status Badges

Add these to your README.md:

```markdown
[![Code Quality & Tests](https://github.com/odanree/odr-image-optimizer/actions/workflows/quality.yml/badge.svg)](https://github.com/odanree/odr-image-optimizer/actions/workflows/quality.yml)
[![Release](https://github.com/odanree/odr-image-optimizer/actions/workflows/release.yml/badge.svg)](https://github.com/odanree/odr-image-optimizer/actions/workflows/release.yml)
```

## Workflows

### quality.yml - Code Quality & Tests

**Triggers:**
- Every push to `main`, `refactor/modern-php-srp`, `develop`
- Every pull request to `main`, `develop`

**Jobs:**
1. **Code Quality Checks** (PHP 8.2, 8.3)
   - Validate composer.json
   - Format check (PHP-CS-Fixer dry-run)
   - Static analysis (PHPStan level max)
   - Unit tests with coverage
   - Caches dependencies for speed

2. **Markdown Lint**
   - Checks all Markdown files for style

3. **Code Coverage Report** (on PRs only)
   - Generates test coverage report
   - Uploads to Codecov for visualization

**Status Check:**
All must pass for merge to main branch (if branch protection enabled).

### release.yml - Automated Releases

**Triggers:**
- Tag push matching `v*` (e.g., `v1.0.0`, `v2.1.0-beta`)

**Jobs:**
1. **Quality Gate**
   - Runs full `composer run check` before release
   - Ensures release is from passing code

2. **Create Release**
   - Extracts version from tag
   - Pulls release notes from CHANGELOG.md for that version
   - Creates GitHub Release with notes
   - Auto-detects pre-releases (tags with `-`)

**Usage:**
```bash
# Create and push a release tag
git tag v1.0.0
git push origin v1.0.0

# Workflow automatically:
# 1. Runs quality checks
# 2. Creates GitHub Release with notes from CHANGELOG.md
```

## Setup for Branch Protection

To require CI/CD passes before merging to `main`:

1. Go to **Settings** → **Branches**
2. Under **Branch protection rules**, click **Add rule**
3. Set branch name to `main`
4. Enable:
   - ✅ Require a pull request before merging
   - ✅ Require status checks to pass before merging
   - Select:
     - Code Quality Checks (all)
     - Markdown Lint
5. Save

Now every PR must pass all checks before merge.

## Local vs CI/CD

### Local (Before Push)
```bash
composer run check     # Run locally first
git push
```

### CI/CD (After Push)
GitHub Actions automatically:
1. Spins up runners (Ubuntu 22.04)
2. Tests on PHP 8.2 and 8.3
3. Caches dependencies for speed
4. Reports results back to PR

## Performance

- **Caching:** Composer dependencies cached per PHP version
- **Parallel:** Multiple PHP versions tested simultaneously
- **Fast:** Typical run time: 2-3 minutes

## Cost

- GitHub Actions: **FREE** for public repos
- Codecov: **FREE** for open source

## Troubleshooting

### Workflow not running?
- Check `.github/workflows/quality.yml` exists and is valid YAML
- Ensure branch name matches trigger conditions

### Tests passing locally but failing in CI?
- CI tests on multiple PHP versions (8.2 and 8.3)
- Check PHP version compatibility
- Enable xdebug locally to match CI: `php -d xdebug.mode=coverage`

### Coverage not uploading?
- Codecov needs public repo or PAT token
- For private repos, add `CODECOV_TOKEN` to GitHub Secrets

## Next Steps

1. Add badges to README.md
2. Enable branch protection on `main`
3. Monitor Actions tab for results
4. Tag a release: `git tag v1.0.0 && git push origin v1.0.0`
