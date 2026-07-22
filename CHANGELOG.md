# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Zones CRUD, activation checks, typed filters, and complete zone hydration.
- DNS record CRUD, PATCH and PUT writes, advanced filters, BIND import/export, legacy and triggered scans, scan review, and batch operations.
- Zone Settings list, get, single update, and bulk update operations, including the `ssl_recommender` enabled variant.
- Account- and zone-scoped Rulesets, entrypoints, versions, managed-version tag filtering, and rule operations through `RulesetScope`.
- Typed entities, enums, query/value objects, action responses, and raw responses for all implemented resources.

### Changed
- **Breaking pre-release redesign:** `Client` now receives an `AdapterInterface`; `Client::withApiToken()` constructs the standard authenticated transport, and concrete endpoint accessors replace the previous client shape.
- **Breaking pre-release redesign:** endpoint writes accept Data Mapper entities instead of payload arrays, and endpoint results use `EntityResponse::getEntity()` or `ListResponse::getItems()` instead of direct entities or iterable wrappers.
- Entities now distinguish present and dirty fields, preserve additional response attributes, hydrate cleanly, and build resource-specific create, patch, and replacement payloads.
- Accounts now use typed entities and response wrappers across list, get, create, update, and delete operations.

### Fixed
- Added PATCH and multipart adapter contracts; normalized GET query, JSON body, empty-body, and raw response handling.
- Preserved response bodies after envelope parsing and converted HTTP or Cloudflare envelope failures into `ErrorResponseException` with available API details.
- Encoded endpoint path segments and rejected absolute or scheme-relative adapter URLs.
- Protected required authentication and `Accept` headers from case-insensitive client or per-request overrides.
- Raised Guzzle and PSR-7 minimums to patched releases after resolving runtime dependency advisories.

### Tests And Tooling
- Replaced live HTTP transport tests with deterministic mocked requests; the test suite no longer requires network access.
- Expanded endpoint, entity, response, transport, utility, and value-object coverage for all implemented resources.
- Fixed strict Composer validation and current PHPStan configuration, added analysis memory limits, and ignored generated test, coverage, fixer, and API documentation output.
- Rewrote usage and contributor guidance for current signatures, response wrappers, entity lifecycle, and development commands.

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
