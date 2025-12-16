# Contributing to Image Optimizer

Thank you for considering a contribution to Image Optimizer! This document provides guidelines and instructions for contributing.

## Code of Conduct

Be respectful, inclusive, and constructive in all interactions.

## Getting Started

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes
4. Write or update tests
5. Ensure code follows WordPress standards
6. Commit with clear messages: `git commit -m 'Add feature: description'`
7. Push to your fork: `git push origin feature/your-feature`
8. Submit a pull request

## Code Standards

This project follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).

### Check Your Code

```bash
# Install dependencies
composer install --dev

# Check code standards
composer run phpcs

# Auto-fix issues
composer run phpcbf
```

## Commit Messages

Use clear, descriptive commit messages:

- `Add feature: description`
- `Fix bug: description`
- `Refactor: description`
- `Docs: description`
- `Test: description`

## Pull Requests

### Before Submitting
- [ ] Code follows WordPress standards
- [ ] All tests pass
- [ ] Documentation is updated
- [ ] Commit messages are clear
- [ ] No merge conflicts

### PR Description
- Describe the change clearly
- Reference any related issues
- Explain the motivation
- Include testing notes

## Reporting Bugs

Use GitHub Issues with:

- Clear title
- Detailed description
- Steps to reproduce
- Expected vs actual behavior
- Environment (PHP, WordPress, plugin versions)

## Suggesting Enhancements

Use GitHub Issues with:

- Clear title
- Detailed description
- Use case/motivation
- Possible implementation approach

## Security Issues

For security vulnerabilities, **do not** open a public issue.
Email: security@danhle.net

## Questions?

- Check existing issues/discussions
- Review documentation
- Ask in PR/issue comments

---

Thank you for contributing! 🙏
