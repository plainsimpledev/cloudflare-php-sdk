# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0]

### Added
- PHP 8.3+ client for Cloudflare API v4 with API token, API key and email, and unauthenticated authentication strategies.
- Typed `Client` accessors and an injectable HTTP adapter abstraction with GET, POST, PUT, PATCH, DELETE, and multipart support.
- Accounts CRUD using typed entities and response wrappers.
- Zones CRUD, activation checks, typed filters, and complete zone hydration.
- DNS record CRUD, PATCH and PUT writes, advanced filters, BIND import/export, legacy and triggered scans, scan review, and batch operations.
- Zone Settings list, get, single update, and bulk update operations, including the `ssl_recommender` enabled variant.
- Account- and zone-scoped Rulesets, entrypoints, versions, managed-version tag filtering, and rule operations through `RulesetScope`.
- Data Mapper entities with present and dirty field tracking, clean hydration, additional response attributes, and operation-specific write payloads.
- Typed entities, enums, query/value objects, page and cursor list responses, action responses, and raw responses for all implemented resources.

### Security
- Restricted transport requests to relative URLs and disabled redirects to prevent authentication leakage.
- Encoded endpoint path segments and rejected absolute or scheme-relative adapter URLs.
- Protected required authentication and `Accept` headers from case-insensitive client or per-request overrides.
- Raised Guzzle and PSR-7 minimums to patched releases after resolving runtime dependency advisories.

### Tests And Tooling
- Deterministic PHPUnit coverage for endpoints, entities, responses, transport, utilities, and value objects without live network calls.
- PHPStan level 7 analysis, PHP CS Fixer formatting, strict Composer validation, and CI on PHP 8.3 and 8.4.
- Usage, contributor, changelog, and agent guidance for current public signatures and entity lifecycle.

[0.1.0]: https://github.com/plainsimpledev/cloudflare-php-sdk/releases/tag/v0.1.0
