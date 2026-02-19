# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project setup with CI/CD pipeline
- PHPStan static analysis (level 7)
- PHP CS Fixer for code style enforcement
- Comprehensive documentation (AGENTS.md, CONTRIBUTING.md)
- MIT License

## [0.1.0] - 2026-02-19

### Added
- Initial release of Cloudflare PHP SDK
- Support for Cloudflare API v4
- Adapter pattern for HTTP clients (GuzzleAdapter)
- Authentication methods (ApiToken, ApiKeyAndEmail, Unauthorized)
- Accounts endpoint with full CRUD operations
- Account and AccountSettings entities
- Response wrappers (ListResponse, EntityResponse)
- Comprehensive test suite with PHPUnit
- PSR-4 autoloading

### Features
- List accounts with pagination support
- Get account by ID
- Create new accounts
- Update existing accounts
- Delete accounts
- Magic property access for entities
- Automatic camelCase to snake_case conversion
- Type-safe entity hydration

[Unreleased]: https://github.com/plainsimple/cloudflare-php-sdk/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/plainsimple/cloudflare-php-sdk/releases/tag/v0.1.0
