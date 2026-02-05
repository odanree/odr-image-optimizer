# Development Workflow

This project uses Composer scripts for automated code quality and testing.

## Quick Start

### Install Dependencies
```bash
composer install
```

### Run All Checks
```bash
composer run check
```

This runs format → analyze → test in sequence. Stops on first failure.

## Individual Commands

### Format Code (PHP-CS-Fixer)
```bash
composer run format
```

Automatically fixes code style issues:
- PSR-12 compliance
- Strict types declaration
- Short array syntax
- Trailing commas in multiline
- Single quotes for strings

### Static Analysis (PHPStan)
```bash
composer run analyze
```

Analyzes code at **level max** (strictest):
- Type safety checks
- Unused variables
- Invalid array access
- Missing return types
- Illegal type hints

**Output example:**
```
 [ERROR] Found 0 errors                                                           
```

### Run Tests (PHPUnit)
```bash
composer run test
```

Runs all tests in `tests/` directory with color output:
- Unit tests for core classes
- Test coverage reporting
- Strict output checking

**Output example:**
```
PHPUnit 11.0.0 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.0
Configuration: phpunit.xml

...
Tests: 3, Assertions: 12, ✓ All green
```

## Workflow Example

### Before Committing
```bash
# Run full check suite
composer run check

# If everything passes:
git add .
git commit -m "feat: add feature"
git push
```

### If Check Fails

**Format error:**
```bash
composer run format
git add .
git commit --amend
```

**Analysis error:**
```bash
# Review phpstan output
# Fix type issues manually
composer run analyze  # verify
```

**Test failure:**
```bash
# Review test output
# Fix implementation
composer run test  # verify
```

## CI/CD Integration

These same commands work in GitHub Actions:

```yaml
- name: Format Check
  run: composer run format

- name: Static Analysis
  run: composer run analyze

- name: Tests
  run: composer run test
```

## PHP Version

This project requires **PHP 8.2+** for:
- Strict types
- Constructor promotion
- Readonly properties
- Union types
- Named arguments
- Match expressions

## Tools Used

| Tool | Purpose | Config |
|------|---------|--------|
| **PHPUnit** | Unit testing | `phpunit.xml` |
| **PHPStan** | Static analysis (level max) | `phpstan.neon` |
| **PHP-CS-Fixer** | Code formatting | `.php-cs-fixer.php` |

## Notes

- `composer run check` is the recommended pre-commit check
- Format code before analysis/tests to avoid false positives
- All configs are in repository root for IDE integration
- Tests directory is excluded from analysis (test code has different rules)
