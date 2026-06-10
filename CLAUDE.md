# ODR Image Optimizer — Project Context

WordPress plugin (PHP 8.1+). Distributed via the WordPress.org plugin directory.

## Public locations

- **Listing**: https://wordpress.org/plugins/odr-image-optimizer/
- **SVN repo**: https://plugins.svn.wordpress.org/odr-image-optimizer
- **GitHub source**: https://github.com/odanree/odr-image-optimizer (default branch `main`)

GitHub is the source of truth; SVN is a one-way deploy target managed by `.github/workflows/deploy-to-wp-org.yml`.

## Releasing a new version

1. Bump the version in **both** places (must stay in sync — Plugin Check fails if `Stable tag` ≠ header `Version`):
   - `odr-image-optimizer.php` → `* Version:` and `* Stable tag:` header lines
   - `readme.txt` → `Stable tag:` line near the top
2. Add changelog entries in `CHANGELOG.md` and the `== Changelog ==` section of `readme.txt`.
3. Merge to `main`.
4. Tag and push: `git tag v1.0.x && git push origin v1.0.x`.
5. Two workflows fire:
   - `release.yml` → runs `composer run check` and publishes a GitHub Release using the matching `## [1.0.x]` section of `CHANGELOG.md` as the body.
   - `deploy-to-wp-org.yml` → syncs the working tree (filtered by `.distignore`) to SVN `trunk/`, creates `tags/1.0.x/`, syncs `.wordpress-org/` → SVN's sibling `assets/` folder.

The deploy action reads `Stable tag:` from `readme.txt` to pick the SVN tag name — the `v` prefix on the git tag is git-only.

## Required GitHub repo secrets

- `WPORG_SVN_USERNAME` — `odanree` (case-sensitive)
- `WPORG_SVN_PASSWORD` — generated under wordpress.org → Account & Security (separate from the wp.org login)

## Directory listing images

Banner / icon / screenshots go in `.wordpress-org/` at repo root. They are **not** shipped inside the plugin zip — only synced to the SVN `assets/` sibling folder. See `.wordpress-org/README.md` for the required filenames and dimensions.

## What ships vs. what stays in dev

`.distignore` controls the trunk/ sync. It excludes `.github/`, `.wordpress-org/`, `tests/`, `composer.json`, `phpstan.neon`, `phpunit.xml`, all `*.md` (including `CHANGELOG.md`, `README.md`, `SUBMISSION.md`), `.lla-*`, `.venv/`, and stray build artifacts (`*.zip`, `*.cache`). Add new dev files there as they appear.

The shipped surface area is intentionally narrow: `assets/` (runtime CSS/JS), `includes/`, `languages/`, `LICENSE`, `odr-image-optimizer.php`, `readme.txt`, `uninstall.php`.

## Backup storage (WordPress.org compliance)

Image backups must live under `wp_upload_dir()['basedir']/odr-image-optimizer/backups/<relative path>/`, **not** in a `.backups/` folder next to media files (the latter triggered the Apr 2026 review rejection). `Optimizer::resolve_backup_path()` is the canonical resolver; `legacy_backup_path()` exists only as a one-time read fallback so users upgrading from 1.0.7 can still revert their old optimizations. Don't reintroduce writes into the plugin folder or into `.backups/` — that breaks compliance.

## Local development

- No local PHP toolchain on the maintainer's machine. Static checks (phpcs/phpstan/phpunit) run in CI via `composer run check`. PHPStan max-level is on; the existing `phpstan.neon` allowlists WordPress symbols since there are no stubs installed — extend that list when adding new WP function/constant references in `includes/Backup/`, `includes/Factory/`, or anywhere outside `includes/core/**` (which is excluded).
- For UI/runtime smoke tests, `npx @wp-playground/cli@latest server --auto-mount` boots WordPress with this plugin pre-mounted. The php-wasm runtime caches bytecode aggressively — drop a mu-plugin calling `opcache_reset()` during live-edit sessions or the playground keeps serving the old class file.
