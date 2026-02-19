# Contributing to Cloudflare PHP SDK

Thank you for your interest in contributing to the Cloudflare PHP SDK! We welcome contributions from everyone.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/cloudflare-php-sdk.git`
3. Install dependencies: `composer install`
4. Create a branch: `git checkout -b feature/your-feature-name`

## Development Setup

### Requirements
- PHP >= 8.3
- Composer
- Git

### Running Tests
```bash
composer test
```

### Code Quality Checks
```bash
# Run all checks (lint, analyse, test)
composer check

# Or run individually
composer lint       # Check code style
composer lint-fix   # Fix code style automatically
composer analyse    # Run static analysis
```

## Code Standards

### PHP Standards
- Use PHP 8.3+ features (readonly classes, union types, etc.)
- Always use `declare(strict_types=1)` at the start of files
- Follow PSR-12 coding standards
- Use type hints for all parameters and return types

### Documentation
- All public methods must have PHPDoc blocks
- Document all parameters, return types, and exceptions
- Use meaningful variable and method names

### Testing
- Write tests for all new code
- Maintain 100% code coverage
- Test both success and error scenarios
- Mock external dependencies

## Submitting Changes

1. Make your changes
2. Add/update tests as needed
3. Ensure all checks pass: `composer check`
4. Update the CHANGELOG.md
5. Commit your changes with a descriptive message
6. Push to your fork
7. Create a Pull Request

### Commit Message Guidelines
- Use present tense: "Add feature" not "Added feature"
- Use imperative mood: "Move cursor to..." not "Moves cursor to..."
- Keep the first line under 50 characters
- Reference issues and PRs when applicable

## Pull Request Process

1. Ensure your PR description clearly describes the problem and solution
2. Link any relevant issues
3. Ensure CI checks pass
4. Request review from maintainers
5. Address any feedback
6. Squash commits if requested

## Reporting Issues

When reporting issues, please include:
- PHP version
- SDK version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Error messages (if any)

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code:
- Be respectful and inclusive
- Welcome newcomers
- Focus on constructive feedback
- Respect different viewpoints and experiences

## Questions?

Feel free to open an issue for questions or join discussions.

Thank you for contributing!
